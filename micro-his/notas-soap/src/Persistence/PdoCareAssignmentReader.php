<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\CareAssignmentReader;
use PDO;

/** Lectura de care_assignments, tabla de Episodios: este módulo nunca escribe en ella. */
final class PdoCareAssignmentReader implements CareAssignmentReader
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function isTreating(string $userId, string $episodeId): bool
    {
        if (!PostgresIds::usable($this->pdo, $userId, $episodeId)) {
            return false;
        }
        // La asignación cuelga del perfil médico; el usuario local está en doctor_profiles.local_user_ref_id. Misma vigencia que el trigger de clinical_notes.
        $stmt = $this->pdo->prepare("SELECT 1 FROM care_assignments a JOIN doctor_profiles p ON p.doctor_profile_id = a.doctor_profile_id WHERE p.local_user_ref_id = ? AND a.care_episode_id = ? AND a.status = 'active' AND a.valid_from <= ? AND a.valid_to IS NULL");
        $stmt->execute([$userId, $episodeId, gmdate('Y-m-d H:i:s') . '+00']);
        return $stmt->fetchColumn() !== false;
    }
}
