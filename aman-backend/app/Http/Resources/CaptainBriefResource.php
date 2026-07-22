<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** عرض مختصر لبيانات الكابتن كما يظهر للزبون أثناء الرحلة */
class CaptainBriefResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'rating_avg' => $this->rating_avg,
            'current_lat' => $this->current_lat,
            'current_lng' => $this->current_lng,
            'vehicle' => $this->whenLoaded('vehicles', fn () => $this->vehicles->first()),
        ];
    }
}
