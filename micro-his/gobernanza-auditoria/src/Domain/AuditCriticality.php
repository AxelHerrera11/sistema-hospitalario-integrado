<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * Política de fallo del evento.
 *
 * Security: el registro es obligatorio; si no se puede persistir, el consumidor
 * recibe AuditStorageFailure y debe abortar o revertir su operación.
 * Informational: el fallo se reporta (FailureReporter) y la operación continúa.
 */
enum AuditCriticality: string
{
    case Security = 'security';
    case Informational = 'informational';

    public function mandatory(): bool
    {
        return $this === self::Security;
    }
}
