<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripDeliveryDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'package_description' => $this->package_description,
            'package_size' => $this->package_size,
        ];
    }
}
