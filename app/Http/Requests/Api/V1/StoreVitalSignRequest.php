<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medical_record_id' => ['required', 'integer', 'exists:medical_records,id'],
            'admission_id' => ['nullable', 'integer', 'exists:admissions,id'],
            'measured_at' => ['required', 'date'],
            'temperature' => ['nullable', 'numeric', 'between:-50,100'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:500'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:200'],
            'systolic_bp' => ['nullable', 'integer', 'min:0', 'max:500'],
            'diastolic_bp' => ['nullable', 'integer', 'min:0', 'max:500'],
            'oxygen_saturation' => ['nullable', 'numeric', 'between:0,100'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'glucose' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
