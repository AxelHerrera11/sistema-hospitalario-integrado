<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
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
                Rule::exists('patients', 'id')->where('tenant_id', $tenant->id),
            ],
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where('tenant_id', $tenant->id),
            ],
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('tenant_id', $tenant->id),
            ],
            'scheduled_at' => ['required', 'date'],
            'duration_min' => ['required', 'integer', 'min:10', 'max:240'],
            'status' => ['prohibited'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
