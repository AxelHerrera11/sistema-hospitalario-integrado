<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

/** Regla de dominio incumplida: estado HTTP sugerido y trace_id cuando hubo denegación. */
final class RbacViolation extends \DomainException
{
    public function __construct(string $code, public readonly int $status, public readonly ?string $traceId = null)
    {
        parent::__construct($code);
    }
}
