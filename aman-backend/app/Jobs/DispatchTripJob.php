<?php

namespace App\Jobs;

use App\Events\NewTripOffer;
use App\Models\Trip;
use App\Services\NotificationService;
use App\Services\TripService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * يُستدعى:
 *  1) فور تحويل الرحلة لحالة "searching" (من TripService::requestTrip)
 *  2) بعد رفض الكابتن للعرض (فورًا، لتجربة زبون أسرع)
 *  3) بعد انتهاء مهلة عرض بلا رد (من CaptainOfferTimeoutJob)
 * يبحث عن أقرب مرشح جديد ويرسل له عرضًا (Push + Broadcast)، أو يقفل الرحلة كـ
 * "no_captain_found" إن نفدت كل دوائر البحث.
 */
class DispatchTripJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public int $tripId)
    {
    }

    public function handle(TripService $tripService, NotificationService $notifications): void
    {
        $trip = Trip::find($this->tripId);

        if (! $trip || $trip->status !== Trip::STATUS_SEARCHING) {
            return; // الرحلة أُلغيت أو قُبلت أو انتهت بالفعل؛ لا داعي لمتابعة البحث
        }

        $result = $tripService->attemptDispatch($trip);

        if (! $result) {
            // لم يُعثر على أي كابتن حتى في أوسع دائرة؛ TripService يحدّث الحالة إلى no_captain_found
            $notifications->notifyCustomer(
                $trip->customer, 'تعذّر إيجاد كابتن', 'لم نجد كابتنًا متاحًا حاليًا، يرجى المحاولة لاحقًا.',
                'trip_no_captain', ['trip_id' => $trip->id],
            );

            return;
        }

        ['captain' => $captain, 'radius_km' => $radius] = $result;

        broadcast(new NewTripOffer($captain->id, $trip));

        $notifications->notifyCaptain(
            $captain, 'طلب رحلة جديد', 'لديك طلب رحلة جديد بالقرب منك (' . round($radius, 1) . ' كم)',
            'trip_offer', ['trip_id' => $trip->id],
        );

        // مهلة استجابة الكابتن قبل الانتقال للمرشح التالي
        CaptainOfferTimeoutJob::dispatch($trip->id, $captain->id)->delay(now()->addSeconds(15));
    }
}
