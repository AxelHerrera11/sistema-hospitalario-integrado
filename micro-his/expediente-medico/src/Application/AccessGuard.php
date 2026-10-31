<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

use MicroHis\ExpedienteMedico\Domain\ExpedienteRuleViolation;

/**
 * Control de acceso del flujo, en dos pasos que no se mezclan: permiso RBAC en el tenant (motor compartido) y,
 * para la consulta, vínculo asistencial con el paciente. Toda denegación se audita sin datos clínicos; si la
 * bitácora falla, la operación sigue denegada (falla segura).
 */
final class AccessGuard
{
    public function __construct(private readonly AccessAuthorizer $authorizer, private readonly ClinicalAccessAudit $audit)
    {
    }

    public function requirePermission(RequestContext $request, string $permissionName): void
    {
        $decision = $this->authorizer->decide($request->actorId, $request->hospitalId, $permissionName);
        if (!$decision->allowed) {
            // rbac_trace une el evento con el «código de seguimiento» que ve el usuario en la respuesta 403.
            $this->deny($request, 'permiso_faltante', ['permiso' => $permissionName, 'rbac_trace' => $decision->traceId]);
            throw new ExpedienteRuleViolation('ACCESO_DENEGADO', 403, $decision->traceId);
        }
    }

    /** @param array<string, int|string> $context */
    public function deny(RequestContext $request, string $reason, array $context = []): void
    {
        try {
            $this->audit->record($request, ClinicalAccessAudit::DENIED, 'denied', null, $reason, $context);
        } catch (AuditUnavailable) {
            // La denegación se mantiene aunque no haya quedado registrada; Gobernanza reporta el fallo por su canal.
        }
    }
}
