<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Models\TripDispatchAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * يُنفَّذ بعد مهلة عرض الرحلة على الكابتن (15 ثانية). إن لم يستجب الكابتن بعد،
 * تُعتبر المحاولة "timeout" ويُعاد تشغيل DispatchTripJob للبحث عن مرشح آخر.
 */
class CaptainOfferTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tripId,
        public int $captainId,
    ) {
    }

    public function handle(): void
    {
        $trip = Trip::find($this->tripId);

        if (! $trip || $trip->status !== Trip::STATUS_SEARCHING) {
            return; // انتهت الرحلة أو قُبلت بالفعل من كابتن آخر (لا ينبغي أن يحدث، لكن للأمان)
        }

        $attempt = TripDispatchAttempt::where('trip_id', $this->tripId)
            ->where('captain_id', $this->captainId)
            ->where('status', 'offered')
            ->latest('id')
            ->first();

        if (! $attempt) {
            return; // الكابتن رد بالفعل (قبول/رفض) قبل انتهاء المهلة
        }

        $attempt->update(['status' => 'timeout', 'responded_at' => now()]);

        DispatchTripJob::dispatch($this->tripId);
    }
}
