<?php

declare(strict_types=1);

namespace App\Prescripciones;

use Closure;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use PDO;
use PDOException;

class PdoPrescriptionContext
{
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private ?PDO $pdo = null;

    public function __construct(private readonly Closure $connect) {}

    public function patientExists(string $patientId): bool
    {
        if (preg_match(self::UUID, $patientId) !== 1) {
            return false;
        }

        return $this->scalar(
            "SELECT 1 FROM local_patients WHERE local_patient_id = :id AND status = 'active'",
            ['id' => $patientId],
        ) !== false;
    }

    public function openCareEpisodeForPatient(string $patientId): ?string
    {
        $episodeId = $this->scalar("SELECT care_episode_id FROM care_episodes WHERE local_patient_id = :patient AND status = 'open' AND ended_at IS NULL ORDER BY started_at DESC, care_episode_id LIMIT 1", ['patient' => $patientId]);

        return $episodeId === false ? null : (string) $episodeId;
    }

    public function doctorProfileForUser(string $centralUserId): ?string
    {
        $profileId = $this->scalar(
            "SELECT dp.doctor_profile_id
             FROM doctor_profiles dp
             JOIN local_user_refs lur ON lur.local_user_ref_id = dp.local_user_ref_id
             WHERE lur.central_user_id = :user
               AND lur.status = 'active'
               AND (lur.valid_to IS NULL OR lur.valid_to > CURRENT_TIMESTAMP)
               AND dp.status = 'active'
               AND dp.valid_from <= CURRENT_DATE
               AND (dp.valid_to IS NULL OR dp.valid_to > CURRENT_DATE)
             ORDER BY dp.valid_from DESC, dp.doctor_profile_id
             LIMIT 1",
            ['user' => $centralUserId],
        );

        return $profileId === false ? null : (string) $profileId;
    }

    private function scalar(string $sql, array $parameters): mixed
    {
        try {
            $statement = ($this->pdo ??= ($this->connect)())->prepare($sql);
            $statement->execute($parameters);

            return $statement->fetchColumn();
        } catch (PDOException $error) {
            throw new PersistenceFailure('No se pudo consultar el contexto clínico.', $error);
        }
    }
}
