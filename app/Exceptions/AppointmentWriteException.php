<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use RuntimeException;

class AppointmentWriteException extends RuntimeException
{
    private function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function fromQueryException(QueryException $exception): self
    {
        $errorInfo = $exception->errorInfo ?? [null, null, $exception->getMessage()];
        $sqlState = (string) $errorInfo[0];
        $dbMessage = (string) $errorInfo[2];

        if ($sqlState === '23P01') {
            return new self(
                'appointments_overlap',
                409,
                'La cita se solapa con otra cita activa del mismo doctor o paciente.',
            );
        }

        if ($sqlState === '23503') {
            return new self(
                'validation_failed',
                422,
                'Uno de los identificadores referenciados no existe.',
            );
        }

        $knownFailures = [
            'appointments_specialty_trigger' => [422, 'El doctor no tiene la especialidad activa en la fecha de la cita.'],
            'appointments_finalized_immutable' => [409, 'La cita finalizada ya no puede modificarse.'],
            'appointments_delete_protection' => [405, 'Las citas no se eliminan; use la cancelación.'],
            'appointments_status_time_check' => [422, 'El horario o la transición de estado no es válida.'],
            'appointments_reason_check' => [422, 'El motivo de la cita no puede estar vacío.'],
        ];

        foreach ($knownFailures as $failure => [$status, $message]) {
            if (str_contains($dbMessage, $failure)) {
                return new self($failure, $status, $message);
            }
        }

        throw $exception;
    }
}
