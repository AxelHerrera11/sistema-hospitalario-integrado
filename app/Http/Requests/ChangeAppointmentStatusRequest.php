<?php

namespace App\Http\Requests;

use App\Services\AppointmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(AppointmentService::STATUSES)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
