<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

/** Cerrado a modificación (Semana 2): todas las reglas deben permitir; sin reglas, niega. */
final class Authorizer
{
    private array $rules = [];

    public function __construct(private readonly PermissionProvider $permissions, private readonly DenialRecorder $denials)
    {
    }

    public function addRule(AuthorizationRule $rule): void
    {
        $this->rules[] = $rule;
    }

    /** Devuelve null si autoriza o el trace_id de la denegación. */
    public function authorize(array $context): ?string
    {
        foreach ($context['roles'] as $role) {
            $context['permissions'][$role] = $this->permissions->permissionsFor($role);
        }
        $reason = $this->rules === [] ? 'sin_reglas' : null;
        foreach ($this->rules as $rule) {
            if (($reason = $rule->decide($context)) !== null) {
                break;
            }
        }
        if ($reason === null) {
            return null;
        }
        $traceId = 'trace-' . bin2hex(random_bytes(6)); // el trace_id lo genera Authorizer
        try {
            $this->denials->record(['user_id' => $context['user_id'], 'hospital_id' => $context['hospital_id'], 'permission' => $context['permission'], 'reason' => $reason, 'trace_id' => $traceId]);
        } catch (\Throwable) {
            // Falla segura: si la bitácora falla, la operación sigue denegada.
        }
        return $traceId;
    }
}
