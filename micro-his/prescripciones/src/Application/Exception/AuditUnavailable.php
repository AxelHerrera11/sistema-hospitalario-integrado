<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Exception;

/** Log de auditoría no disponible. */
final class AuditUnavailable extends \RuntimeException
{
    public function __construct(string $message = 'Auditoría no disponible.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
