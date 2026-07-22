<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'city_id' => $this->city_id,
            'commission_type' => $this->commission_type,
            'value' => $this->value,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from,
        ];
    }
}
