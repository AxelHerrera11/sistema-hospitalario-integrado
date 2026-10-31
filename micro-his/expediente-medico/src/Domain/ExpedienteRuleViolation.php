<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Domain;

/**
 * Regla del expediente incumplida: código estable, estado HTTP sugerido y, si hubo denegación RBAC, su trace_id.
 * El mensaje es solo el código: nunca contiene nombres, motivos de consulta ni otro dato clínico.
 */
final class ExpedienteRuleViolation extends \DomainException
{
    public function __construct(string $code, public readonly int $status, public readonly ?string $traceId = null)
    {
        parent::__construct($code);
    }
}
