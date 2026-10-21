<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Application;

use MicroHis\Rbac\Domain\AuthorizationRule;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleResolver;
use MicroHis\Rbac\Domain\TrackedDenialRecorder;

/**
 * Caso de uso: decidir si un usuario puede ejecutar una acción en un hospital.
 *
 * Construye internamente el motor Authorizer con un único TrackedDenialRecorder
 * compartido: no existe forma pública de cablear un Authorizer y un seguimiento
 * de auditoría distintos, ni de omitir la detección de fallos del DenialRecorder.
 * Devuelve el contrato estable allowed/reason/trace_id. Falla segura:
 *
 *  - Si RoleResolver o PermissionProvider lanzan, se deniega con
 *    reason "resolucion_no_disponible", sin filtrar el detalle de la excepción.
 *  - Si DenialRecorder lanza (el motor ya deniega), se distingue con
 *    reason "registro_no_disponible", conservando allowed=false y trace_id.
 *  - Una denegación ordinaria queda con reason "permiso_faltante".
 *  - Nunca se devuelve allowed=true tras un fallo de resolución o auditoría.
 */
final class Authorize
{
    private readonly Authorizer $authorizer;
    private readonly TrackedDenialRecorder $audit;

    public function __construct(
        private readonly RoleResolver $roles,
        PermissionProvider $permissions,
        DenialRecorder $denials,
        AuthorizationRule ...$rules,
    ) {
        $this->audit = new TrackedDenialRecorder($denials);
        $this->authorizer = new Authorizer($permissions, $this->audit);
        foreach ($rules as $rule) {
            $this->authorizer->addRule($rule);
        }
    }

    /** @return array{allowed: bool, reason: ?string, trace_id: string} */
    public function execute(string $userId, string $hospitalId, string $permission): array
    {
        try {
            $roles = $this->roles->rolesIn($userId, $hospitalId);
            $denial = $this->authorizer->authorize([
                'user_id' => $userId,
                'hospital_id' => $hospitalId,
                'permission' => $permission,
                'roles' => $roles,
            ]);
        } catch (\Throwable) {
            return self::denied('resolucion_no_disponible');
        }

        if ($denial === null) {
            return ['allowed' => true, 'reason' => null, 'trace_id' => self::newTraceId()];
        }

        if ($this->audit->failed()) {
            return self::denied('registro_no_disponible', $denial);
        }

        return ['allowed' => false, 'reason' => 'permiso_faltante', 'trace_id' => $denial];
    }

    /** @return array{allowed: false, reason: string, trace_id: string} */
    private static function denied(string $reason, ?string $traceId = null): array
    {
        return ['allowed' => false, 'reason' => $reason, 'trace_id' => $traceId ?? self::newTraceId()];
    }

    private static function newTraceId(): string
    {
        return 'trace-' . bin2hex(random_bytes(6));
    }
}
