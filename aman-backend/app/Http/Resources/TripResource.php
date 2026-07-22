<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_code' => $this->trip_code,
            'status' => $this->status,
            'trip_mode' => $this->trip_mode,
            'service' => [
                'id' => $this->service->id,
                'code' => $this->service->code,
                'name' => $this->service->{'name_' . app()->getLocale()},
            ],
            'pickup' => [
                'address' => $this->pickup_address,
                'lat' => $this->pickup_lat,
                'lng' => $this->pickup_lng,
            ],
            'dropoff' => $this->when($this->dropoff_lat, [
                'address' => $this->dropoff_address,
                'lat' => $this->dropoff_lat,
                'lng' => $this->dropoff_lng,
            ]),
            'scheduled_at' => $this->scheduled_at,
            'distance_km' => $this->distance_km,
            'duration_min' => $this->duration_min,
            'estimated_price' => $this->estimated_price,
            'final_price' => $this->final_price,
            'currency' => $this->currency,
            'captain' => new CaptainBriefResource($this->whenLoaded('captain')),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'full_name' => $this->customer->full_name,
                'phone' => $this->customer->phone,
            ]),
            'delivery_details' => new TripDeliveryDetailResource($this->whenLoaded('deliveryDetails')),
            'requested_at' => $this->requested_at,
            'accepted_at' => $this->accepted_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
