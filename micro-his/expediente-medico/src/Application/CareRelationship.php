<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/**
 * ¿El usuario tiene un vínculo asistencial activo con el paciente? Lectura de care_assignments + doctor_profiles
 * + care_episodes, las mismas tablas que usan los triggers de integridad de la migración hospital 000400.
 * SUPUESTO S-01: la consulta exige este vínculo además del permiso RBAC (ver docs/expedientes-medicos/semana-02).
 */
interface CareRelationship
{
    /** @throws PersistenceFailure si el almacén no responde */
    public function hasActiveAssignment(string $userRefId, string $patientId): bool;
}
