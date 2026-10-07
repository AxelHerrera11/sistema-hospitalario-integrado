<?php

namespace App\Http\Requests;

use App\Models\Medication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('tenant');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('medications', 'name')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($this->medication()?->id),
            ],
            'generic_name' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'presentation' => ['nullable', 'string', 'max:50'],
            'concentration' => ['nullable', 'string', 'max:50'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    private function medication(): ?Medication
    {
        $medication = $this->route('medication');

        return $medication instanceof Medication ? $medication : null;
    }
}
