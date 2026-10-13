<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cie10_code' => ['required', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:200'],
            'type' => ['nullable', Rule::in(['principal', 'secundario', 'presuntivo', 'definitivo'])],
        ];
    }
}
