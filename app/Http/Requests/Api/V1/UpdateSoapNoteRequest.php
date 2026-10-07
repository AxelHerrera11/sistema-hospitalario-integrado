<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSoapNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subjective' => ['sometimes', 'string'],
            'objective' => ['sometimes', 'string'],
            'assessment' => ['sometimes', 'string'],
            'plan' => ['sometimes', 'string'],
        ];
    }
}
