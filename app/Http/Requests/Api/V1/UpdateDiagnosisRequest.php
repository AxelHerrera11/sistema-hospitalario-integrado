<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cie10_code' => ['sometimes', 'string', 'max:10'],
            'description' => ['sometimes', 'string', 'max:200'],
            'type' => ['sometimes', Rule::in(['principal', 'secundario', 'presuntivo', 'definitivo'])],
        ];
    }
}
