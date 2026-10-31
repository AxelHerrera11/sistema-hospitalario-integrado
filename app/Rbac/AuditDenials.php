<?php

declare(strict_types=1);

namespace App\Rbac;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use MicroHis\Rbac\Domain\DenialRecorder;

/**
 * Conecta las denegaciones RBAC al registro de auditoría persistente.
 *
 * El evento cumple el contrato de gobernanza (`docs/gobernanza-auditoria/
 * semana-05/contrato-api.md`): resultado `denied`, criticidad `security` y
 * recurso `permission`. Sin esas columnas el lector clasifica la denegación
 * como resultado desconocido y el filtro por resultado no la devuelve.
 */
final class AuditDenials implements DenialRecorder
{
    public function __construct(private readonly AuditLogRepositoryInterface $audit) {}

    public function record(array $denial): void
    {
        $permission = $denial['permission'] ?? null;

        $this->audit->logRaw('rbac.access_denied', [
            'user_id' => $denial['user_id'] ?? null,
            'actor_id' => $denial['user_id'] ?? null,
            'hospital_id' => $denial['hospital_id'] ?? null,
            'tenant_id' => $denial['hospital_id'] ?? null,
            'permission' => $permission,
            'reason' => $denial['reason'] ?? null,
            'trace_id' => $denial['trace_id'] ?? null,
            'result' => 'denied',
            'criticality' => 'security',
            'resource' => $permission === null ? null : ['type' => 'permission', 'id' => $permission],
        ], 'warning');
    }
}
