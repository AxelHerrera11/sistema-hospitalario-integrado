<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Persistence;

use MicroHis\SignosVitales\Application\CareEpisodeReader;
use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Application\VitalSignRepository;
use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\VitalSigns;
use PDO;

/** Persistencia de mediciones y auditoría con PDO. Los valores siempre van como parámetros de sentencias preparadas. */
final class PdoVitalSignStore implements VitalSignRepository, CareEpisodeReader
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function contextFor(string $userId, string $episodeId): ?EpisodeContext
    {
        $stmt = $this->pdo->prepare("SELECT e.status, e.patient_birth_date FROM care_episodes e JOIN care_assignments a ON a.care_episode_id = e.care_episode_id
            WHERE a.user_ref_id = ? AND e.care_episode_id = ? AND a.status = 'active'");
        $stmt->execute([$userId, $episodeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : new EpisodeContext($row['status'] === 'open', (new \DateTimeImmutable($row['patient_birth_date']))->diff(new \DateTimeImmutable())->y);
    }

    public function record(string $episodeId, string $userId, VitalSigns $vitals, string $measuredAt, ?Assessment $assessment): string
    {
        $id = 'sv-' . bin2hex(random_bytes(4));
        // Los nombres de columna salen de VitalSigns::LIMITS (lista blanca validada en el dominio), nunca de la entrada.
        $columns = implode(', ', array_keys($vitals->values));
        $marks = implode(', ', array_fill(0, count($vitals->values), '?'));
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("INSERT INTO vital_signs (vital_sign_id, care_episode_id, recorded_by, measured_at, $columns) VALUES (?, ?, ?, ?, $marks)")
                ->execute([$id, $episodeId, $userId, $measuredAt, ...array_values($vitals->values)]);
            $this->audit('signo_vital.registrado', $userId, $id);
            if ($assessment === null) {
                $this->audit('signo_vital.sin_evaluacion', $userId, $id . ':THRESHOLDS_NOT_APPLICABLE');
            } elseif ($assessment->requiresAlert()) {
                $this->audit('signo_vital.alerta_generada', $userId, $id . ':' . strtolower($assessment->severity->name) . ':' . implode(',', array_keys($assessment->findings)));
            }
            $this->pdo->commit();
        } catch (\PDOException $e) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new \RuntimeException('persistencia_no_disponible', 0, $e);
        }
        return $id;
    }

    private function audit(string $event, string $actorId, string $detail): void
    {
        $this->pdo->prepare('INSERT INTO local_access_audits (event, actor_id, detail) VALUES (?, ?, ?)')->execute([$event, $actorId, $detail]);
    }
}
