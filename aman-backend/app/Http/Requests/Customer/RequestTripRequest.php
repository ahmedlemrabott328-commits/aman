<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class RequestTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية تُتحقق عبر middleware auth:sanctum
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'trip_mode' => ['nullable', 'in:instant,scheduled,open'],

            'pickup_address' => ['required', 'string', 'max:255'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],

            'dropoff_address' => ['nullable', 'string', 'max:255'],
            'dropoff_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['nullable', 'numeric', 'between:-180,180'],

            'scheduled_at' => ['required_if:trip_mode,scheduled', 'nullable', 'date', 'after:now'],

            'distance_km' => ['required', 'numeric', 'min:0'],
            'duration_min' => ['required', 'integer', 'min:0'],

            // خدمة التوصيل فقط
            'delivery' => ['required_if:service_id,3', 'nullable', 'array'],
            'delivery.receiver_name' => ['required_with:delivery', 'string', 'max:150'],
            'delivery.receiver_phone' => ['required_with:delivery', 'string', 'max:20'],
            'delivery.package_size' => ['nullable', 'in:small,medium,large'],

            // خدمة المطار فقط
            'airport' => ['nullable', 'array'],
            'airport.flight_number' => ['nullable', 'string', 'max:20'],
        ];
    }
}
