<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Exceptions\TripException;
use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(
        private TripService $tripService,
        private TripRepositoryInterface $tripRepository,
    ) {
    }

    /** قبول طلب رحلة عُرض على الكابتن */
    public function accept(Request $request, int $id)
    {
        $trip = $this->tripRepository->findOrFail($id);

        try {
            $trip = $this->tripService->acceptByCaptain($trip, $request->user());
        } catch (TripException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new TripResource($trip->load(['customer', 'service'])), 'تم قبول الرحلة');
    }

    public function reject(Request $request, int $id)
    {
        $trip = $this->tripRepository->findOrFail($id);
        $this->tripService->rejectByCaptain($trip, $request->user());

        return $this->success(null, 'تم رفض الطلب');
    }

    public function arrived(Request $request, int $id)
    {
        $trip = $this->tripRepository->findOrFail($id);
        $trip = $this->tripService->markArrived($trip, $request->user());

        return $this->success(new TripResource($trip->load(['customer', 'service'])));
    }

    /** بدء الرحلة يدويًا */
    public function start(Request $request, int $id)
    {
        $trip = $this->tripRepository->findOrFail($id);

        try {
            $trip = $this->tripService->startTrip($trip, $request->user());
        } catch (TripException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new TripResource($trip->load(['customer', 'service'])), 'تم بدء الرحلة');
    }

    /** إنهاء الرحلة يدويًا مع القيم الفعلية للمسافة/الوقت */
    public function complete(Request $request, int $id)
    {
        $data = $request->validate([
            'actual_distance_km' => ['required', 'numeric', 'min:0'],
            'actual_duration_min' => ['required', 'integer', 'min:0'],
        ]);

        $trip = $this->tripRepository->findOrFail($id);

        try {
            $trip = $this->tripService->completeTrip(
                $trip, $request->user(), $data['actual_distance_km'], $data['actual_duration_min'],
            );
        } catch (TripException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new TripResource($trip->load(['customer', 'service'])), 'تم إنهاء الرحلة');
    }

    public function history(Request $request)
    {
        $trips = $this->tripRepository->historyForCaptain($request->user()->id);

        $trips->getCollection()->load(['customer', 'service']);

        return $this->success(TripResource::collection($trips));
    }
}
