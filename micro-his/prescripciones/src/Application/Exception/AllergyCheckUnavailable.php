<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Exception;

/** Servicio de alergias no disponible (timeout, 5xx, etc.). */
final class AllergyCheckUnavailable extends \RuntimeException
{
    public function __construct(string $message = 'Servicio de alergias no disponible.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
