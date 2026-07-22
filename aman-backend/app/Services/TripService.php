<?php

namespace App\Services;

use App\Exceptions\TripException;
use App\Events\TripStatusChanged;
use App\Jobs\DispatchTripJob;
use App\Models\Captain;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\Trip;
use App\Models\TripStatusHistory;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private PricingService $pricingService,
        private TripDispatchService $dispatchService,
        private WalletService $walletService,
        private NotificationService $notificationService,
    ) {
    }

    /**
     * طلب رحلة جديدة (فورية أو مجدولة أو مفتوحة).
     * ملاحظة: الزبون لا يختار كابتنًا؛ النظام يسند تلقائيًا لاحقًا.
     */
    public function requestTrip(Customer $customer, array $data): Trip
    {
        if ($this->tripRepository->currentForCustomer($customer->id)) {
            throw new TripException('customer_has_active_trip');
        }

        $estimate = $this->pricingService->estimate(
            serviceId: $data['service_id'],
            cityId: $data['city_id'],
            distanceKm: $data['distance_km'],
            durationMin: $data['duration_min'],
            vehicleTypeId: $data['vehicle_type_id'] ?? null,
        );

        return DB::transaction(function () use ($customer, $data, $estimate) {
            $trip = Trip::create([
                'customer_id' => $customer->id,
                'service_id' => $data['service_id'],
                'city_id' => $data['city_id'],
                'trip_mode' => $data['trip_mode'] ?? Trip::MODE_INSTANT,
                'status' => Trip::STATUS_REQUESTED,
                'pickup_address' => $data['pickup_address'],
                'pickup_lat' => $data['pickup_lat'],
                'pickup_lng' => $data['pickup_lng'],
                'dropoff_address' => $data['dropoff_address'] ?? null,
                'dropoff_lat' => $data['dropoff_lat'] ?? null,
                'dropoff_lng' => $data['dropoff_lng'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'distance_km' => $data['distance_km'],
                'duration_min' => $data['duration_min'],
                'estimated_price' => $estimate['estimated_price'],
                'currency' => $estimate['currency'],
                'pricing_rule_id' => $estimate['pricing_rule_id'],
            ]);

            // بيانات إضافية حسب نوع الخدمة (نمط Extension Table)
            if (! empty($data['delivery'])) {
                $trip->deliveryDetails()->create($data['delivery']);
            }
            if (! empty($data['airport'])) {
                $trip->airportDetails()->create($data['airport']);
            }

            $this->logStatus($trip, Trip::STATUS_REQUESTED, 'customer', $customer->id);

            // الرحلات الفورية تدخل بحث فوري؛ المجدولة تُفعَّل عبر Job قبل الموعد
            if ($trip->trip_mode === Trip::MODE_INSTANT) {
                $trip->update(['status' => Trip::STATUS_SEARCHING]);
                $this->logStatus($trip, Trip::STATUS_SEARCHING, 'system', null);
                DispatchTripJob::dispatch($trip->id);
            }

            return $trip->fresh();
        });
    }

    /**
     * محاولة إسناد الرحلة لأقرب كابتن متاح (يُستدعى من DispatchTripJob).
     *
     * @return array{captain:Captain, radius_km:float}|null
     */
    public function attemptDispatch(Trip $trip): ?array
    {
        if ($trip->status !== Trip::STATUS_SEARCHING) {
            return null;
        }

        $alreadyOffered = $trip->dispatchAttempts()->pluck('captain_id')->toArray();
        $candidate = $this->dispatchService->findNextCandidate($trip, $alreadyOffered);

        if (! $candidate) {
            $trip->update(['status' => Trip::STATUS_NO_CAPTAIN_FOUND]);
            $this->logStatus($trip, Trip::STATUS_NO_CAPTAIN_FOUND, 'system', null);

            return null;
        }

        return $candidate;
    }

    public function acceptByCaptain(Trip $trip, Captain $captain): Trip
    {
        if ($trip->status !== Trip::STATUS_SEARCHING) {
            throw new TripException('trip_not_available');
        }
        if (! $captain->isAvailable()) {
            throw new TripException('captain_not_available');
        }

        $trip->update([
            'captain_id' => $captain->id,
            'status' => Trip::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $this->dispatchService->markAttemptResult($trip->id, $captain->id, 'accepted');
        $this->logStatus($trip, Trip::STATUS_ACCEPTED, 'captain', $captain->id);

        broadcast(new TripStatusChanged($trip));
        $this->notificationService->notifyCustomer(
            $trip->customer, 'تم العثور على كابتن', "الكابتن {$captain->full_name} في طريقه إليك",
            'trip_accepted', ['trip_id' => $trip->id, 'captain_id' => $captain->id],
        );

        return $trip->fresh();
    }

    public function rejectByCaptain(Trip $trip, Captain $captain): void
    {
        $this->dispatchService->markAttemptResult($trip->id, $captain->id, 'rejected');
        // إعادة البحث فورًا عن مرشح آخر بدل انتظار مهلة الـ 15 ثانية => تجربة زبون أسرع
        DispatchTripJob::dispatch($trip->id);
    }

    public function markArrived(Trip $trip, Captain $captain): Trip
    {
        $this->assertOwnership($trip, $captain);
        $trip->update(['status' => Trip::STATUS_ARRIVED, 'arrived_at' => now()]);
        $this->logStatus($trip, Trip::STATUS_ARRIVED, 'captain', $captain->id);

        broadcast(new TripStatusChanged($trip));
        $this->notificationService->notifyCustomer(
            $trip->customer, 'وصل الكابتن', 'الكابتن بانتظارك في نقطة الانطلاق',
            'trip_arrived', ['trip_id' => $trip->id],
        );

        return $trip->fresh();
    }

    /** بدء الرحلة يدويًا من طرف الكابتن */
    public function startTrip(Trip $trip, Captain $captain): Trip
    {
        $this->assertOwnership($trip, $captain);
        if ($trip->status !== Trip::STATUS_ARRIVED) {
            throw new TripException('trip_not_arrived_yet');
        }

        $trip->update(['status' => Trip::STATUS_IN_PROGRESS, 'started_at' => now()]);
        $this->logStatus($trip, Trip::STATUS_IN_PROGRESS, 'captain', $captain->id);

        broadcast(new TripStatusChanged($trip));

        return $trip->fresh();
    }

    /** إنهاء الرحلة يدويًا: احتساب السعر النهائي والعمولة وتحديث المحفظة */
    public function completeTrip(Trip $trip, Captain $captain, float $actualDistanceKm, int $actualDurationMin): Trip
    {
        $this->assertOwnership($trip, $captain);
        if ($trip->status !== Trip::STATUS_IN_PROGRESS) {
            throw new TripException('trip_not_in_progress');
        }

        return DB::transaction(function () use ($trip, $captain, $actualDistanceKm, $actualDurationMin) {
            $estimate = $this->pricingService->estimate(
                $trip->service_id, $trip->city_id, $actualDistanceKm, $actualDurationMin,
            );

            $commissionRule = CommissionRule::where('service_id', $trip->service_id)
                ->where(fn ($q) => $q->where('city_id', $trip->city_id)->orWhereNull('city_id'))
                ->where('is_active', true)
                ->orderByDesc('effective_from')
                ->first();

            $commissionAmount = $commissionRule?->calculateFor($estimate['estimated_price']) ?? 0;

            $trip->update([
                'status' => Trip::STATUS_COMPLETED,
                'completed_at' => now(),
                'distance_km' => $actualDistanceKm,
                'duration_min' => $actualDurationMin,
                'final_price' => $estimate['estimated_price'],
                'commission_rule_id' => $commissionRule?->id,
                'commission_amount' => $commissionAmount,
            ]);

            $this->logStatus($trip, Trip::STATUS_COMPLETED, 'captain', $captain->id);

            // الدفع مباشر من الزبون للكابتن؛ نسجل فقط الأرباح والعمولة المستحقة بالمحفظة
            $this->walletService->settleTrip($captain->id, $trip->id, (float) $trip->final_price, (float) $commissionAmount);

            broadcast(new TripStatusChanged($trip));
            $this->notificationService->notifyCustomer(
                $trip->customer, 'اكتملت الرحلة', "المبلغ المستحق: {$trip->final_price} {$trip->currency}",
                'trip_completed', ['trip_id' => $trip->id],
            );

            return $trip->fresh();
        });
    }

    public function cancel(Trip $trip, string $cancelledByType, ?int $cancelledById, ?string $reason = null): Trip
    {
        if (! $trip->isCancellable()) {
            throw new TripException('trip_not_cancellable');
        }

        $trip->update([
            'status' => Trip::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by_type' => $cancelledByType,
            'cancelled_by_id' => $cancelledById,
            'cancel_reason' => $reason,
        ]);

        $this->logStatus($trip, Trip::STATUS_CANCELLED, $cancelledByType, $cancelledById, $reason);

        broadcast(new TripStatusChanged($trip));

        // إشعار الطرف الآخر (من لم يُلغِ هو من يُبلَّغ)
        if ($cancelledByType === 'customer' && $trip->captain) {
            $this->notificationService->notifyCaptain(
                $trip->captain, 'أُلغيت الرحلة', 'ألغى الزبون الرحلة', 'trip_cancelled', ['trip_id' => $trip->id],
            );
        } elseif ($cancelledByType === 'captain') {
            $this->notificationService->notifyCustomer(
                $trip->customer, 'أُلغيت الرحلة', 'ألغى الكابتن الرحلة، يمكنك طلب رحلة جديدة الآن.',
                'trip_cancelled', ['trip_id' => $trip->id],
            );
            // ملاحظة: إعادة البحث التلقائي عن كابتن بديل لنفس الرحلة بعد إلغاء الكابتن
            // (بدل مطالبة الزبون بطلب رحلة جديدة يدويًا) قرار منتجي يحتاج تأكيدًا؛ غير مُفعَّل حاليًا.
        }

        return $trip->fresh();
    }

    private function assertOwnership(Trip $trip, Captain $captain): void
    {
        if ($trip->captain_id !== $captain->id) {
            throw new TripException('not_your_trip');
        }
    }

    private function logStatus(Trip $trip, string $status, string $byType, ?int $byId, ?string $note = null): void
    {
        TripStatusHistory::create([
            'trip_id' => $trip->id,
            'status' => $status,
            'changed_by_type' => $byType,
            'changed_by_id' => $byId,
            'note' => $note,
        ]);
    }
}
