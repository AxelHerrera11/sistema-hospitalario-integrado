<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSoapNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medical_record_id' => ['required', 'integer', 'exists:medical_records,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'admission_id' => ['nullable', 'integer', 'exists:admissions,id'],
            'subjective' => ['required', 'string'],
            'objective' => ['required', 'string'],
            'assessment' => ['required', 'string'],
            'plan' => ['required', 'string'],
        ];
    }
}
