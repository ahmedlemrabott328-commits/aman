<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PricingRuleRequest extends FormRequest
{
    public function authorize(): bool { return true; } // التحقق فعليًا عبر permission:pricing.manage

    public function rules(): array
    {
        $ruleId = $this->route('pricing_rule');

        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'base_fare' => ['required', 'numeric', 'min:0'],
            'price_per_km' => ['required', 'numeric', 'min:0'],
            'price_per_minute' => ['required', 'numeric', 'min:0'],
            'min_fare' => ['required', 'numeric', 'min:0'],
            'cancellation_fee' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
        ];
    }
}
