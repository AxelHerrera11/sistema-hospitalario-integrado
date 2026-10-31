<?php

declare(strict_types=1);

namespace App\ExpedienteMedico;

use RuntimeException;

/** Regla del vínculo asistencial incumplida: código estable para la API y estado HTTP. */
final class CareAssignmentFailure extends RuntimeException
{
    public function __construct(public readonly string $errorCode, public readonly int $status)
    {
        parent::__construct($errorCode);
    }
}
