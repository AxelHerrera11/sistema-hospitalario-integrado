<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** Operación válida en sí misma pero no en el estado actual de la ejecución (HTTP 409). */
final class RunStateConflict extends \DomainException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
