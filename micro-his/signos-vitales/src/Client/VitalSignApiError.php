<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Client;

/** El servidor respondió con error: conserva el estado, el code del contrato y el trace_id para rastrearlo. */
final class VitalSignApiError extends \RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, public readonly string $traceId)
    {
        parent::__construct("$status $errorCode (trace_id $traceId)");
    }
}
