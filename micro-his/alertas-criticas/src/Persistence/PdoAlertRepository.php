<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Persistence;

use Closure;
use MicroHis\Alertas\Application\AlertRepository;
use MicroHis\Alertas\Application\PersistenceFailure;
use MicroHis\Alertas\Domain\CriticalAlert;
use PDO;
use PDOException;

/**
 * Adaptador PDO de critical_alerts y local_access_audits; solo lee lab_tests y la relación de cuidado vigente
 * (care_assignments → doctor_profiles → local_user_ref_id). En la base, la jefatura del servicio es un destinatario NULL.
 */
final class PdoAlertRepository implements AlertRepository
{
    private const COLUMNS = "critical_alert_id, lab_result_version_id, local_patient_id, care_episode_id, lab_test_id, measured_value, direction,
        COALESCE(CAST(current_recipient_user_ref_id AS TEXT), '".CriticalAlert::SERVICE_HEAD."') AS current_recipient, escalation_level, due_at, status,
        acknowledged_by_user_ref_id";

    private const CARE = "FROM care_assignments a JOIN doctor_profiles p ON p.doctor_profile_id = a.doctor_profile_id
        WHERE a.status = 'active' AND a.valid_to IS NULL";

    private ?PDO $connection = null;

    /** @param PDO|Closure(): PDO $pdo con un Closure la conexión se abre en el primer uso: una base caída es PersistenceFailure, no un error al construir */
    public function __construct(private readonly PDO|Closure $pdo) {}

    public function thresholds(string $labTestId): ?array
    {
        $row = $this->read('SELECT critical_low, critical_high FROM lab_tests WHERE lab_test_id = ? AND critical_low IS NOT NULL
            AND critical_high IS NOT NULL', [$labTestId])->fetch(PDO::FETCH_ASSOC);
        return $row ? array_map('floatval', $row) : null;
    }

    public function attending(string $episodeId): ?string
    {
        return $this->read('SELECT p.local_user_ref_id '.self::CARE.' AND a.care_episode_id = ? ORDER BY a.valid_from DESC', [$episodeId])->fetchColumn() ?: null;
    }

    public function caresFor(string $user, string $patientId): bool
    {
        return $this->read('SELECT 1 '.self::CARE.' AND p.local_user_ref_id = ? AND a.care_episode_id IN
            (SELECT care_episode_id FROM care_episodes WHERE local_patient_id = ?)', [$user, $patientId])->fetchColumn() !== false;
    }

    public function find(string $alertId): ?CriticalAlert
    {
        $row = $this->read('SELECT '.self::COLUMNS.' FROM critical_alerts WHERE critical_alert_id = ?', [$alertId])->fetch(PDO::FETCH_ASSOC);
        return $row ? CriticalAlert::fromRow($row) : null;
    }

    public function pendingFor(string $user): array
    {
        $rows = $this->read('SELECT '.self::COLUMNS." FROM critical_alerts WHERE COALESCE(CAST(current_recipient_user_ref_id AS TEXT), ?) = ?
            AND status = 'open' ORDER BY due_at", [CriticalAlert::SERVICE_HEAD, $user])->fetchAll(PDO::FETCH_ASSOC);
        return array_map([CriticalAlert::class, 'fromRow'], $rows);
    }

    public function save(CriticalAlert $a, string $event, string $actor): void
    {
        try {
            $this->pdo()->beginTransaction();
            if ($a->status === 'open') {
                $this->query('INSERT INTO critical_alerts (critical_alert_id, lab_result_version_id, local_patient_id, care_episode_id, lab_test_id,
                    measured_value, direction, current_recipient_user_ref_id, escalation_level, due_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$a->id,
                    $a->resultVersionId, $a->patientId, $a->episodeId, $a->labTestId, $a->value, $a->direction,
                    $a->recipient === CriticalAlert::SERVICE_HEAD ? null : $a->recipient, $a->level, $a->dueAt]);
            } elseif ($this->query("UPDATE critical_alerts SET status = 'acknowledged', acknowledged_by_user_ref_id = ?, acknowledged_at = ?
                WHERE critical_alert_id = ? AND status = 'open'", [$a->acknowledgedBy, date(DATE_ATOM), $a->id])->rowCount() !== 1) {
                throw new PDOException('la alerta ya fue acusada', 23000); // otro usuario acusó antes
            }
            $this->query("INSERT INTO local_access_audits (correlation_id, actor_user_ref_id, module, action, resource_type, resource_id, local_patient_id,
                care_episode_id, occurred_at) VALUES (?, ?, 'alertas_criticas', ?, 'critical_alert', ?, ?, ?, ?)",
                [bin2hex(random_bytes(16)), $actor, $event, $a->id, $a->patientId, $a->episodeId, date(DATE_ATOM)]);
            $this->pdo()->commit();
        } catch (PDOException $error) {
            $this->connection?->inTransaction() && $this->connection->rollBack();
            // Solo el UNIQUE por resultado (23505; 23000 en SQLite) o el acuse ya hecho significan «repetido».
            throw new PersistenceFailure(in_array((string) $error->getCode(), ['23000', '23505'], true), $error);
        }
    }

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    /** Las lecturas fuera de save() también traducen la falla de la base. */
    private function read(string $sql, array $params): \PDOStatement
    {
        try {
            return $this->query($sql, $params);
        } catch (PDOException $error) {
            throw new PersistenceFailure(false, $error);
        }
    }

    private function pdo(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->pdo instanceof Closure ? ($this->pdo)() : $this->pdo;
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->connection;
    }
}
