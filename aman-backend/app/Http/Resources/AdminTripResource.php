<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** عرض موسّع للرحلة كما تحتاجه لوحة الإدارة (يشمل الزبون وتفاصيل الإلغاء) */
class AdminTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_code' => $this->trip_code,
            'status' => $this->status,
            'trip_mode' => $this->trip_mode,
            'service' => $this->whenLoaded('service', fn () => $this->service->code),
            'city' => $this->whenLoaded('city', fn () => $this->city->{'name_' . app()->getLocale()}),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'full_name' => $this->customer->full_name,
                'phone' => $this->customer->phone,
            ]),
            'captain' => new CaptainBriefResource($this->whenLoaded('captain')),
            'pickup_address' => $this->pickup_address,
            'dropoff_address' => $this->dropoff_address,
            'distance_km' => $this->distance_km,
            'duration_min' => $this->duration_min,
            'estimated_price' => $this->estimated_price,
            'final_price' => $this->final_price,
            'commission_amount' => $this->commission_amount,
            'currency' => $this->currency,
            'cancelled_by_type' => $this->cancelled_by_type,
            'cancel_reason' => $this->cancel_reason,
            'requested_at' => $this->requested_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
        ];
    }
}
