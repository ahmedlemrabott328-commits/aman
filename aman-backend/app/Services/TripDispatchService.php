<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripDispatchAttempt;
use App\Repositories\Contracts\CaptainRepositoryInterface;

class TripDispatchService
{
    // دوائر البحث المتوسعة تدريجيًا (كم) - قابلة للنقل لجدول settings مستقبلاً
    private const SEARCH_RADII_KM = [2, 5, 10, 15];

    public function __construct(
        private CaptainRepositoryInterface $captainRepository,
    ) {
    }

    /**
     * البحث عن أقرب كابتن متاح، مع توسيع دائرة البحث تدريجيًا عند عدم وجود نتيجة.
     * يُستدعى من Job متكرر (كل بضع ثوانٍ) طالما الرحلة في حالة "searching".
     *
     * @return array{captain:\App\Models\Captain, radius_km:float}|null
     */
    public function findNextCandidate(Trip $trip, array $excludeCaptainIds = []): ?array
    {
        foreach (self::SEARCH_RADII_KM as $radius) {
            $candidates = $this->captainRepository->nearbyAvailable(
                lat: $trip->pickup_lat,
                lng: $trip->pickup_lng,
                radiusKm: $radius,
                serviceId: $trip->service_id,
                excludeIds: $excludeCaptainIds,
            );

            if ($candidates->isNotEmpty()) {
                $captain = $candidates->first();

                TripDispatchAttempt::create([
                    'trip_id' => $trip->id,
                    'captain_id' => $captain->id,
                    'search_radius_km' => $radius,
                    'status' => 'offered',
                    'offered_at' => now(),
                ]);

                return ['captain' => $captain, 'radius_km' => $radius];
            }
        }

        return null; // لم يُعثر على كابتن حتى في أوسع دائرة => no_captain_found
    }

    public function markAttemptResult(int $tripId, int $captainId, string $status): void
    {
        TripDispatchAttempt::where('trip_id', $tripId)
            ->where('captain_id', $captainId)
            ->latest('id')
            ->first()
            ?->update(['status' => $status, 'responded_at' => now()]);
    }
}
