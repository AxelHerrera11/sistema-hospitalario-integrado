<?php

declare(strict_types=1);

namespace MicroHis\Pacientes\Presentation;

use MicroHis\Pacientes\Domain\Patient;

final class PatientView
{
    public function patient(Patient $patient, int $status = 200): array
    {
        return [
            'status' => $status,
            'body' => [
                'patient' => [
                    'id' => $patient->id,
                    'global_id' => $patient->globalId,
                    'code' => $patient->code,
                    'given_name' => $patient->givenName,
                    'family_name' => $patient->familyName,
                    'birth_date' => $patient->birthDate,
                    'sex' => $patient->sex,
                    'contact' => $patient->contact,
                    'status' => $patient->status,
                ],
            ],
        ];
    }

    public function error(
        int $status,
        string $code,
        string $message,
        ?string $field = null
    ): array {
        return [
            'status' => $status,
            'body' => [
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'field' => $field,
                ],
            ],
        ];
    }
}
