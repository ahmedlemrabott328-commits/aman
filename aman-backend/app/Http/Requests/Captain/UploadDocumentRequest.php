<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // التحقق فعليًا عبر auth:sanctum على مستوى الـ route
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'in:license,id_card,vehicle_registration,insurance'],
            // 5MB حد أقصى، صور أو PDF فقط — كافٍ لصور وثائق واضحة دون إثقال الرفع على شبكات محدودة السرعة
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'يجب أن تكون الوثيقة صورة (jpg/png) أو ملف PDF',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 5 ميغابايت',
        ];
    }
}
