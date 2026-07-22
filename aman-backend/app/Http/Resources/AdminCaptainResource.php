<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCaptainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'national_id' => $this->national_id,
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->{'name_' . app()->getLocale()},
            ]),
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->rejection_reason,
            'is_online' => $this->is_online,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'documents' => CaptainDocumentResource::collection($this->whenLoaded('documents')),
            'vehicles' => VehicleResource::collection($this->whenLoaded('vehicles')),
            'services' => $this->whenLoaded('services', fn () => $this->services->pluck('code')),
            'created_at' => $this->created_at,
            'approved_at' => $this->approved_at,
        ];
    }
}
