<?php

declare(strict_types=1);

namespace App\Rbac;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use MicroHis\Rbac\Domain\DenialRecorder;

/** Conecta las denegaciones RBAC al registro de auditoría persistente. */
final class AuditDenials implements DenialRecorder
{
    public function __construct(private readonly AuditLogRepositoryInterface $audit) {}

    public function record(array $denial): void
    {
        $this->audit->logRaw('rbac.access_denied', [
            'user_id' => $denial['user_id'] ?? null,
            'actor_id' => $denial['user_id'] ?? null,
            'hospital_id' => $denial['hospital_id'] ?? null,
            'tenant_id' => $denial['hospital_id'] ?? null,
            'permission' => $denial['permission'] ?? null,
            'reason' => $denial['reason'] ?? null,
            'trace_id' => $denial['trace_id'] ?? null,
        ], 'warning');
    }
}
