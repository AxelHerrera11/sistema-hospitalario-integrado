<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSoapNoteRequest extends FormRequest
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
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where('tenant_id', $tenant->id),
            ],
            'admission_id' => [
                'nullable',
                'integer',
                Rule::exists('admissions', 'id')->where('tenant_id', $tenant->id),
            ],
            'subjective' => ['required', 'string'],
            'objective' => ['required', 'string'],
            'assessment' => ['required', 'string'],
            'plan' => ['required', 'string'],
        ];
    }
}