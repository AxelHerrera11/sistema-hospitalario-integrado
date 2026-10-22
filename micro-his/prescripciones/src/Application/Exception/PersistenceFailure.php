<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Exception;

/** Fallo de persistencia (base de datos, red, etc.). */
final class PersistenceFailure extends \RuntimeException
{
    public function __construct(string $message = 'Error de persistencia.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}