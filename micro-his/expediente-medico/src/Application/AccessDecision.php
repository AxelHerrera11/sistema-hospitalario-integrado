<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/** Resultado del motor RBAC con su contrato estable allowed/reason/trace_id (MicroHis\Rbac\Application\Authorize). */
final readonly class AccessDecision
{
    public function __construct(public bool $allowed, public ?string $reason, public string $traceId)
    {
    }
}
