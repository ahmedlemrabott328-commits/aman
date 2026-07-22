<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'city_id' => $this->city_id,
            'vehicle_type_id' => $this->vehicle_type_id,
            'base_fare' => $this->base_fare,
            'price_per_km' => $this->price_per_km,
            'price_per_minute' => $this->price_per_minute,
            'min_fare' => $this->min_fare,
            'cancellation_fee' => $this->cancellation_fee,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from,
        ];
    }
}
