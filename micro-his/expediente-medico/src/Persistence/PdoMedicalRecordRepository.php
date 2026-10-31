<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Persistence;

use MicroHis\ExpedienteMedico\Application\MedicalRecordRepository;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use MicroHis\ExpedienteMedico\Domain\CareEpisode;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;
use PDO;

/**
 * Adaptador PDO con sentencias preparadas sobre las tablas reales de la base HOSPITAL (migraciones 000200 y 000400).
 * Escribe solo en medical_records; care_episodes es de solo lectura. Funciona con pgsql y con sqlite (pruebas).
 * Ningún error del driver sale de aquí: se traduce a PersistenceFailure con un código interno.
 */
final class PdoMedicalRecordRepository implements MedicalRecordRepository
{
    private const RECORD = 'SELECT medical_record_id, local_patient_id, record_number, opened_at, status, closed_at FROM medical_records WHERE local_patient_id = ?';
    private const EPISODES = 'SELECT care_episode_id, local_patient_id, type, status, started_at, ended_at, reason, service_code FROM care_episodes WHERE local_patient_id = ? ORDER BY started_at DESC';
    private const INSERT = "INSERT INTO medical_records (medical_record_id, local_patient_id, record_number, opened_at, status) VALUES (?, ?, ?, ?, 'open')";

    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function findByPatient(string $patientId): ?MedicalRecord
    {
        $row = $this->guard(fn () => $this->select(self::RECORD, [$patientId])[0] ?? null, 'record_read_failed');

        return $row === null ? null : RowMapper::record($row);
    }

    public function open(MedicalRecord $record, callable $confirm): MedicalRecord
    {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare(self::INSERT)->execute([$record->id, $record->patientId, $record->recordNumber, $record->openedAt->format('Y-m-d H:i:sP')]);
            $confirm($record);
            $this->pdo->commit();

            return $record;
        } catch (\PDOException $e) {
            $this->rollBack();
            // 23505 (pgsql) / 23000 (sqlite): otro proceso abrió el expediente del mismo paciente; se devuelve ese.
            if (in_array((string) $e->getCode(), ['23505', '23000'], true) && ($winner = $this->findByPatient($record->patientId)) !== null) {
                return $winner;
            }
            throw new PersistenceFailure('record_write_failed');
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function episodesOf(string $patientId): array
    {
        $rows = $this->guard(fn () => $this->select(self::EPISODES, [$patientId]), 'episodes_read_failed');

        return array_map(static fn (array $row): CareEpisode => RowMapper::episode($row), $rows);
    }

    /** @return list<array<string, mixed>> */
    private function select(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function guard(callable $read, string $code): mixed
    {
        try {
            return $read();
        } catch (\PDOException) {
            throw new PersistenceFailure($code);
        }
    }

    private function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
