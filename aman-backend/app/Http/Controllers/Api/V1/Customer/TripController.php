<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\TripException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RequestTripRequest;
use App\Http\Resources\TripResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\PricingService;
use App\Services\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(
        private TripService $tripService,
        private TripRepositoryInterface $tripRepository,
        private PricingService $pricingService,
    ) {
    }

    /** عرض السعر التقديري قبل تأكيد الطلب */
    public function estimate(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'distance_km' => ['required', 'numeric', 'min:0'],
            'duration_min' => ['required', 'integer', 'min:0'],
            'vehicle_type_id' => ['nullable', 'integer'],
        ]);

        $estimate = $this->pricingService->estimate(
            $data['service_id'], $data['city_id'], $data['distance_km'],
            $data['duration_min'], $data['vehicle_type_id'] ?? null,
        );

        return $this->success($estimate);
    }

    /** طلب رحلة جديدة */
    public function store(RequestTripRequest $request)
    {
        try {
            $trip = $this->tripService->requestTrip($request->user(), $request->validated());
        } catch (TripException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new TripResource($trip->load(['service'])), 'تم إنشاء الرحلة', 201);
    }

    /** الرحلة الحالية النشطة (لتتبع الكابتن مباشرة) */
    public function current(Request $request)
    {
        $trip = $this->tripRepository->currentForCustomer($request->user()->id);

        if (! $trip) {
            return $this->success(null);
        }

        return $this->success(new TripResource($trip->load(['service', 'captain.vehicles'])));
    }

    public function show(Request $request, int $id)
    {
        $trip = $this->tripRepository->findOrFail($id);
        $this->authorizeOwnership($trip, $request);

        return $this->success(new TripResource($trip->load(['service', 'captain.vehicles', 'deliveryDetails'])));
    }

    /** سجل الرحلات */
    public function history(Request $request)
    {
        $trips = $this->tripRepository->historyForCustomer($request->user()->id);

        return $this->success(TripResource::collection($trips));
    }

    /** إلغاء الرحلة */
    public function cancel(Request $request, int $id)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $trip = $this->tripRepository->findOrFail($id);
        $this->authorizeOwnership($trip, $request);

        try {
            $trip = $this->tripService->cancel($trip, 'customer', $request->user()->id, $data['reason'] ?? null);
        } catch (TripException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new TripResource($trip), 'تم إلغاء الرحلة');
    }

    /** تقييم الرحلة بعد انتهائها — القسم 11 */
    public function rate(Request $request, int $id)
    {
        $data = $request->validate([
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $trip = $this->tripRepository->findOrFail($id);
        $this->authorizeOwnership($trip, $request);

        if ($trip->status !== \App\Models\Trip::STATUS_COMPLETED) {
            return $this->error('trip_not_completed', 422);
        }
        if ($trip->rating()->exists()) {
            return $this->error('trip_already_rated', 422);
        }

        $rating = $trip->rating()->create([
            'customer_id' => $trip->customer_id,
            'captain_id' => $trip->captain_id,
            'score' => $data['score'],
            'comment' => $data['comment'] ?? null,
        ]);

        // تحديث متوسط تقييم الكابتن
        $captain = $trip->captain;
        $newCount = $captain->rating_count + 1;
        $newAvg = (($captain->rating_avg * $captain->rating_count) + $data['score']) / $newCount;
        $captain->update(['rating_avg' => round($newAvg, 2), 'rating_count' => $newCount]);

        return $this->success($rating, 'شكرًا لتقييمك');
    }

    private function authorizeOwnership($trip, Request $request): void
    {
        abort_if($trip->customer_id !== $request->user()->id, 403, 'unauthorized');
    }
}
