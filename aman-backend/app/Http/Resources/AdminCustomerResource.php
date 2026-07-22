<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'trips_count' => $this->whenCounted('trips'),
            'created_at' => $this->created_at,
            'last_login_at' => $this->last_login_at,
        ];
    }
}
