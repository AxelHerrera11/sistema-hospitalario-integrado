<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Integration;

use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Rbac\Domain\DenialRecorder;

/**
 * Implementación de DenialRecorder (micro-his/rbac) sobre AuditLogger.
 *
 * Recibe exactamente el arreglo que construye Authorizer:
 * user_id, hospital_id, permission, reason, trace_id.
 * Es un evento Security: si no se puede guardar, lanza; Authorizer lo atrapa,
 * mantiene la denegación y Authorize responde "registro_no_disponible" (503).
 * Requiere cargar micro-his/rbac/bootstrap.php antes de usar esta clase.
 */
final class RbacDenialRecorder implements DenialRecorder
{
    public function __construct(private readonly RecordAuditEvent $recorder)
    {
    }

    public function record(array $denial): void
    {
        $permission = is_string($denial['permission'] ?? null) ? $denial['permission'] : null;
        $reason = is_string($denial['reason'] ?? null) ? $denial['reason'] : null;

        $this->recorder->record(
            (string) ($denial['user_id'] ?? ''),
            (string) ($denial['hospital_id'] ?? ''),
            'rbac',
            'access_denied',
            AuditOutcome::Denied,
            AuditCriticality::Security,
            resourceType: $permission === null ? null : 'permission',
            resourceId: $permission,
            correlationId: is_string($denial['trace_id'] ?? null) ? $denial['trace_id'] : null,
            reason: $reason,
            context: ['permission' => $permission],
        );
    }
}
