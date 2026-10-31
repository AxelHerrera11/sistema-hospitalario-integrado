<?php

declare(strict_types=1);

namespace App\Http\Requests\Prescriptions;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medication_id' => ['required', 'string', 'uuid'],
            'dosage_value' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:9999999.999'],
            'dosage_unit' => ['required', 'string', 'min:1', 'max:30'],
            'route' => ['required', 'string', 'in:oral,intravenosa,intramuscular,subcutanea,topica,inhalada'],
            'frequency_times' => ['required', 'integer', 'min:1'],
            'frequency_interval' => ['nullable', 'string', 'max:40'],
            'duration_value' => ['required', 'integer', 'min:1'],
            'duration_unit' => ['required', 'string', 'min:1', 'max:30'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'code' => 'VALIDATION_FAILED',
                'message' => 'La solicitud no es válida.',
                'errors' => $validator->errors(),
            ], 422)->withHeaders(['Content-Type' => 'application/problem+json', 'Cache-Control' => 'no-store, private']),
        );
    }
}
