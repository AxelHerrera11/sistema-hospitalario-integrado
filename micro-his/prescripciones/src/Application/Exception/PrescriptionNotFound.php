<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Exception;

use RuntimeException;

final class PrescriptionNotFound extends RuntimeException
{
    public function __construct(string $prescriptionId)
    {
        parent::__construct('Prescripción no encontrada: '.$prescriptionId);
    }
}
