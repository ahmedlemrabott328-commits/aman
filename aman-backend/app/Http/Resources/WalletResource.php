<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'captain' => $this->whenLoaded('captain', fn () => [
                'id' => $this->captain->id,
                'full_name' => $this->captain->full_name,
                'phone' => $this->captain->phone,
            ]),
            'balance' => $this->balance,
            'currency' => $this->currency,
            'updated_at' => $this->updated_at,
        ];
    }
}
