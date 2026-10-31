<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Persistence;

use MicroHis\ExpedienteMedico\Application\CareRelationship;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use PDO;

/**
 * Vínculo asistencial leído de las tablas de Médicos/Episodios (este módulo nunca escribe en ellas). La condición es
 * la misma que exige el trigger enforce_clinical_note_integrity (migración 000400) para que un médico escriba en
 * el expediente: asignación activa, vigente y sin valid_to, de un perfil médico activo del usuario.
 */
final class PdoCareRelationship implements CareRelationship
{
    private const SQL = "SELECT 1 FROM care_assignments a
        JOIN doctor_profiles p ON p.doctor_profile_id = a.doctor_profile_id
        JOIN care_episodes e ON e.care_episode_id = a.care_episode_id
        WHERE p.local_user_ref_id = ? AND e.local_patient_id = ? AND p.status = 'active'
          AND a.status = 'active' AND a.valid_to IS NULL AND a.valid_from <= CURRENT_TIMESTAMP LIMIT 1";

    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function hasActiveAssignment(string $userRefId, string $patientId): bool
    {
        try {
            $stmt = $this->pdo->prepare(self::SQL);
            $stmt->execute([$userRefId, $patientId]);

            return $stmt->fetchColumn() !== false;
        } catch (\PDOException) {
            throw new PersistenceFailure('relationship_read_failed');
        }
    }
}
