<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllergyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active' => ['sometimes', 'boolean'],
            'allergen_type' => ['sometimes', Rule::in(['medicamento', 'alimento', 'ambiental', 'otro'])],
            'severity' => ['sometimes', Rule::in(['leve', 'moderada', 'grave', 'anafilaxis'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
