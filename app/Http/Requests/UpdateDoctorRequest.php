<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('tenant');
        $doctor = $this->route('doctor');

        return [
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('tenant_id', $tenant->id),
            ],
            'license_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('doctors', 'license_number')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($doctor?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
