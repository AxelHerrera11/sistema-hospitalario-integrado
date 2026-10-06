<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('tenant');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', $tenant->id),
                Rule::unique('doctors', 'user_id')->where('tenant_id', $tenant->id),
            ],
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('tenant_id', $tenant->id),
            ],
            'license_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('doctors', 'license_number')->where('tenant_id', $tenant->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = User::query()->find($this->integer('user_id'));

                if (! $user?->hasRole('Médico')) {
                    $validator->errors()->add('user_id', 'El usuario seleccionado debe tener el rol Médico.');
                }
            },
        ];
    }
}
