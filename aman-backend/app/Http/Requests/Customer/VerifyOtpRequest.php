<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+222[0-9]{8}$/'],
            'code' => ['required', 'string', 'size:4'],
            'full_name' => ['nullable', 'string', 'max:150'], // عند أول تسجيل
        ];
    }
}
