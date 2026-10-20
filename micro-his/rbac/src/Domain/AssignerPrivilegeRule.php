<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

/** Regla nueva de Semana 3 (OCP): el rol asignado no puede superar el privilegio del asignador. */
final class AssignerPrivilegeRule implements AuthorizationRule
{
    /** Niveles de ejemplo: supuesto SD-RBAC-09, pendiente de validación. */
    public const LEVELS = ['SuperAdmin' => 100, 'AdminHospital' => 80, 'Medico' => 50, 'Bioquimico' => 50, 'Enfermeria' => 40, 'TecnicoLab' => 40, 'Recepcionista' => 20];

    public function decide(array $context): ?string
    {
        if (!isset($context['role_to_assign'])) {
            return null;
        }
        $max = max([0, ...array_map(static fn (string $r): int => self::LEVELS[$r] ?? 0, $context['roles'])]);
        return self::LEVELS[$context['role_to_assign']] <= $max ? null : 'rol_supera_privilegio';
    }
}
