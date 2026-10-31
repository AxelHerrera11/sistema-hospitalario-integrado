<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Solo prescribe y firma el médico tratante del episodio activo del paciente (SD-PRE-03). */
final class NotTreatingPhysician extends \RuntimeException
{
    public function __construct(string $message = 'No es médico tratante del episodio activo del paciente.')
    {
        parent::__construct($message);
    }
}
