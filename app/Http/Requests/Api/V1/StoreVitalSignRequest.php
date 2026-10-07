<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('tenant');

        return [
            'medical_record_id' => [
                'required',
                'integer',
                Rule::exists('medical_records', 'id')->where('tenant_id', $tenant->id),
            ],
            'admission_id' => [
                'nullable',
                'integer',
                Rule::exists('admissions', 'id')->where('tenant_id', $tenant->id),
            ],
            'measured_at' => ['required', 'date'],
            'temperature' => ['nullable', 'numeric', 'between:-50,100'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:500'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:200'],
            'systolic_bp' => ['nullable', 'integer', 'min:0', 'max:500'],
            'diastolic_bp' => ['nullable', 'integer', 'min:0', 'max:500'],
            'oxygen_saturation' => ['nullable', 'numeric', 'between:0,100'],
            // decimal(5,2) en BD -> máximo 999.99
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            // decimal(6,2) en BD -> máximo 9999.99
            'glucose' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }
}