<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('PUT') ? 'sometimes' : 'required';

        return [
            'allergen' => [$presence, 'string', 'max:150'],
            'allergen_type' => [$presence, Rule::in(['medicamento', 'alimento', 'ambiental', 'otro'])],
            'severity' => [$presence, Rule::in(['leve', 'moderada', 'grave', 'anafilaxis'])],
            'reaction' => ['sometimes', 'nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
