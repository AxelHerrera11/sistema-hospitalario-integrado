<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Solo se firma un borrador: si ya está firmada o anulada, la respuesta es 409. */
final class PrescriptionNotDraft extends \RuntimeException
{
    public function __construct(string $message = 'La prescripción ya está firmada o anulada.')
    {
        parent::__construct($message);
    }
}
