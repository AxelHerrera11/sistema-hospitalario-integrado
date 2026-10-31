<?php

declare(strict_types=1);

namespace App\NotasSoap;

use MicroHis\NotasSoap\Application\CareAssignmentReader;
use PDO;

/**
 * Médico tratante en la base HOSPITAL real: perfil médico del usuario local con asignación activa y vigente al episodio.
 * Es el mismo criterio que exige el trigger `clinical_notes_author_assignment_trigger` (migración 000400).
 */
final class HospitalCareAssignmentReader implements CareAssignmentReader
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function isTreating(string $userId, string $episodeId): bool
    {
        if (! HospitalClinicalNoteStore::isUuid($userId) || ! HospitalClinicalNoteStore::isUuid($episodeId)) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT 1 FROM care_assignments a JOIN doctor_profiles p ON p.doctor_profile_id = a.doctor_profile_id
            WHERE p.local_user_ref_id = ? AND a.care_episode_id = ? AND a.status = 'active' AND a.valid_from <= now() AND a.valid_to IS NULL");
        $stmt->execute([$userId, $episodeId]);

        return $stmt->fetchColumn() !== false;
    }
}
