<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'center_lat' => $this->center_lat,
            'center_lng' => $this->center_lng,
            'is_active' => $this->is_active,
        ];
    }
}
