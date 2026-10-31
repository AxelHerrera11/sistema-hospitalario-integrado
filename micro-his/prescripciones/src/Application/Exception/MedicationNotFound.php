<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Exception;

/** Medicamento no encontrado o inactivo. */
final class MedicationNotFound extends \DomainException
{
    public function __construct(string $medicationId)
    {
        parent::__construct("Medicamento '$medicationId' no encontrado o inactivo.");
    }
}
