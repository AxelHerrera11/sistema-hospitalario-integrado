<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Persistence;

use MicroHis\SignosVitales\Application\StoredVitalSign;
use MicroHis\SignosVitales\Application\VitalSignRepository;
use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\Uuid;
use MicroHis\SignosVitales\Domain\VitalSigns;
use PDO;

/** Adaptador PDO del Repository de mediciones y su auditoría. Los valores siempre van como parámetros de sentencias preparadas. */
final class PdoVitalSignRepository implements VitalSignRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function record(string $episodeId, string $userId, VitalSigns $vitals, string $measuredAt, ?Assessment $assessment): string
    {
        $id = Uuid::v4();
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

    public function find(string $vitalSignId): ?StoredVitalSign
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vital_signs WHERE vital_sign_id = ?');
        $stmt->execute([$vitalSignId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        $values = [];
        foreach (array_keys(VitalSigns::LIMITS) as $sign) {
            $row[$sign] === null || $values[$sign] = (float) $row[$sign];
        }
        return new StoredVitalSign($row['vital_sign_id'], $row['care_episode_id'], $row['recorded_by'], $row['measured_at'], new VitalSigns($values));
    }

    private function audit(string $event, string $actorId, string $detail): void
    {
        $this->pdo->prepare('INSERT INTO local_access_audits (event, actor_id, detail) VALUES (?, ?, ?)')->execute([$event, $actorId, $detail]);
    }
}
