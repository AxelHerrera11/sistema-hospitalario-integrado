<?php

declare(strict_types=1);

namespace MicroHis\Admision\Persistence;

use MicroHis\Admision\Application\AdmissionStore;
use MicroHis\Admision\Application\PersistenceFailure;
use MicroHis\Admision\Domain\Admission;
use PDO;
use PDOException;

/** Adaptador PDO de care_episodes, bed_stays, beds.status y local_access_audits. Solo sentencias preparadas. */
final class PdoAdmissionStore implements AdmissionStore
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function patientExists(string $patientId): bool
    {
        return $this->query('SELECT 1 FROM local_patients WHERE local_patient_id = ?', [$patientId])->fetchColumn() !== false;
    }

    public function hasOpenInpatientEpisode(string $patientId): bool
    {
        return $this->query("SELECT 1 FROM care_episodes WHERE local_patient_id = ? AND type = 'inpatient' AND status = 'open'",
            [$patientId])->fetchColumn() !== false;
    }

    public function bed(string $bedId): ?array
    {
        return $this->query('SELECT bed_id, type, status FROM beds WHERE bed_id = ?', [$bedId])->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function save(Admission $a, string $actor): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->query("INSERT INTO care_episodes (care_episode_id, local_patient_id, type, started_at, reason) VALUES (?, ?, 'inpatient', ?, ?)",
                [$a->episodeId, $a->patientId, $a->startedAt, $a->reason]);
            $this->query('INSERT INTO bed_stays (bed_stay_id, care_episode_id, bed_id, assigned_by_user_ref_id, started_at, reason)
                VALUES (?, ?, ?, ?, ?, ?)', [$a->stayId, $a->episodeId, $a->bedId, $actor, $a->startedAt, $a->reason]);
            if ($this->query("UPDATE beds SET status = 'occupied' WHERE bed_id = ? AND status = 'available'", [$a->bedId])->rowCount() !== 1) {
                throw new PDOException('la cama dejó de estar disponible', 23000);
            }
            $this->query("INSERT INTO local_access_audits (correlation_id, actor_user_ref_id, module, action, resource_type, resource_id, local_patient_id,
                care_episode_id, occurred_at) VALUES (?, ?, 'admision', 'admision.cama_asignada', 'bed_stay', ?, ?, ?, ?)",
                [bin2hex(random_bytes(16)), $actor, $a->stayId, $a->patientId, $a->episodeId, $a->startedAt]);
            $this->pdo->commit();
        } catch (PDOException $error) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new PersistenceFailure(str_starts_with((string) $error->getCode(), '23'), $error);
        }
    }

    public function availableBeds(string $type): array
    {
        return $this->query("SELECT bed_id, code, type FROM beds WHERE status = 'available' AND (? = '' OR type = ?) ORDER BY code",
            [$type, $type])->fetchAll(PDO::FETCH_ASSOC);
    }

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }
}
