<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Domain\Audit;

interface AuditLogRepositoryInterface
{
    /**
     * Persiste un evento de auditoría con el contrato de micro-his/rbac.
     *
     * El payload conserva user_id, hospital_id/tenant_id, permission, reason y
     * trace_id. Los valores ausentes quedan como null dentro del detalle JSON.
     */
    public function logRaw(string $eventName, array $payload, ?string $severity = null): void;
}
