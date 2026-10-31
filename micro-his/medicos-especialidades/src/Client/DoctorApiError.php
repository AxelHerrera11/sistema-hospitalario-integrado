<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Client;

/** El servidor respondió con error: el cliente conserva estado, código y trace_id para mostrarlo o reportarlo. */
final class DoctorApiError extends \RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, public readonly string $traceId)
    {
        parent::__construct("$status $errorCode (trace_id $traceId)");
    }
}
