<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('tenant');

        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')
                    ->where('tenant_id', $tenant->id),
            ],

            'bed_id' => [
                'required',
                'integer',
                Rule::exists('beds', 'id')
                    ->where('tenant_id', $tenant->id),
            ],

            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')
                    ->where('tenant_id', $tenant->id),
            ],
        ];
    }
}
