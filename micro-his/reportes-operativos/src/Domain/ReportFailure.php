<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Domain;

use RuntimeException;

/**
 * Error de dominio con su estado HTTP y su código de contrato. Se llama
 * errorCode para no chocar con Exception::$code, que es entero.
 */
final class ReportFailure extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
