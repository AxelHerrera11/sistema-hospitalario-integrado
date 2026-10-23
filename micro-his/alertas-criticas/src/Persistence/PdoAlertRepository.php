<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Persistence;

use MicroHis\Alertas\Application\AlertRepository;
use MicroHis\Alertas\Application\PersistenceFailure;
use MicroHis\Alertas\Domain\CriticalAlert;
use PDO;
use PDOException;

/** Adaptador PDO de critical_alerts y local_access_audits; solo lee lab_tests y care_assignments. */
final class PdoAlertRepository implements AlertRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function thresholds(string $labTestId): ?array
    {
        $row = $this->query('SELECT critical_low, critical_high FROM lab_tests WHERE lab_test_id = ? AND critical_low IS NOT NULL
            AND critical_high IS NOT NULL', [$labTestId])->fetch(PDO::FETCH_ASSOC);
        return $row ? array_map('floatval', $row) : null;
    }

    public function attending(string $episodeId): ?string
    {
        return $this->query("SELECT user_ref_id FROM care_assignments WHERE care_episode_id = ? AND status = 'active'", [$episodeId])->fetchColumn() ?: null;
    }

    public function caresFor(string $user, string $patientId): bool
    {
        return $this->query("SELECT 1 FROM care_assignments WHERE user_ref_id = ? AND local_patient_id = ? AND status = 'active'",
            [$user, $patientId])->fetchColumn() !== false;
    }

    public function find(string $alertId): ?CriticalAlert
    {
        $row = $this->query('SELECT * FROM critical_alerts WHERE critical_alert_id = ?', [$alertId])->fetch(PDO::FETCH_ASSOC);
        return $row ? CriticalAlert::fromRow($row) : null;
    }

    public function pendingFor(string $user): array
    {
        $rows = $this->query("SELECT * FROM critical_alerts WHERE current_recipient = ? AND status = 'open' ORDER BY due_at", [$user])->fetchAll(PDO::FETCH_ASSOC);
        return array_map([CriticalAlert::class, 'fromRow'], $rows);
    }

    public function save(CriticalAlert $a, string $event, string $actor): void
    {
        try {
            $this->pdo->beginTransaction();
            if ($a->status === 'open') {
                $this->query('INSERT INTO critical_alerts (critical_alert_id, lab_result_version_id, local_patient_id, lab_test_id, measured_value,
                    direction, current_recipient, escalation_level, due_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$a->id, $a->resultVersionId,
                    $a->patientId, $a->labTestId, $a->value, $a->direction, $a->recipient, $a->level, $a->dueAt]);
            } elseif ($this->query("UPDATE critical_alerts SET status = 'acknowledged', acknowledged_by_user_ref_id = ?
                WHERE critical_alert_id = ? AND status = 'open'", [$a->acknowledgedBy, $a->id])->rowCount() !== 1) {
                throw new PDOException('la alerta ya fue acusada', 23000); // otro usuario acusó antes
            }
            $this->query("INSERT INTO local_access_audits (correlation_id, actor_user_ref_id, module, action, resource_type, resource_id, local_patient_id, occurred_at)
                VALUES (?, ?, 'alertas_criticas', ?, 'critical_alert', ?, ?, ?)", [bin2hex(random_bytes(16)), $actor, $event, $a->id, $a->patientId, date(DATE_ATOM)]);
            $this->pdo->commit();
        } catch (PDOException $error) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new PersistenceFailure(str_starts_with((string) $error->getCode(), '23'), $error);
        }
    }

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}
