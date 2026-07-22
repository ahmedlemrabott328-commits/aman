<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTripResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(private TripRepositoryInterface $tripRepository)
    {
    }

    public function index(Request $request)
    {
        $trips = $this->tripRepository->paginate(
            perPage: (int) $request->get('per_page', 20),
            filters: $request->only(['status', 'service_id', 'city_id', 'from', 'to']),
        );

        $trips->load(['service', 'city', 'customer', 'captain']);

        return $this->success(AdminTripResource::collection($trips)->response()->getData(true));
    }

    public function show(int $id)
    {
        $trip = $this->tripRepository->findOrFail($id);
        $trip->load(['service', 'city', 'customer', 'captain.vehicles', 'statusHistory', 'rating', 'deliveryDetails', 'airportDetails']);

        return $this->success(new AdminTripResource($trip));
    }
}
