<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Persistence;

use DateTimeImmutable;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\PrescriptionRecord;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\Prescription;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use MicroHis\Prescripciones\Domain\PrescriptionStatus;
use PDO;
use PDOException;
final readonly class PdoPrescriptionRepository implements PrescriptionRepository
{
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private bool $postgres;
    public function __construct(private PDO $pdo)
    {
        $this->postgres = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public function transaction(callable $operation): mixed
    {
        $nested = $this->pdo->inTransaction();
        $savepoint = 'prescription_'.bin2hex(random_bytes(8));
        $nested ? $this->pdo->exec('SAVEPOINT '.$savepoint) : $this->pdo->beginTransaction();
        try {
            $result = $operation();
            $nested ? $this->pdo->exec('RELEASE SAVEPOINT '.$savepoint) : $this->pdo->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                if ($nested) {
                    $this->pdo->exec('ROLLBACK TO SAVEPOINT '.$savepoint);
                    $this->pdo->exec('RELEASE SAVEPOINT '.$savepoint);
                } else {
                    $this->pdo->rollBack();
                }
            }
            throw $error;
        }
    }
    public function save(PrescriptionRecord $record): void
    {
        $prescription = $record->prescription;
        $fields = [
            'id' => $prescription->id,
            'patient' => $prescription->patientId,
            'episode' => $prescription->careEpisodeId,
            'medication' => $prescription->medication->id,
            'prescriber' => $prescription->doctorProfileId,
            'signer' => $prescription->signedBy,
            'dose' => $record->doseAmount,
            'unit' => $record->doseUnit,
            'route' => $prescription->route->value,
            'frequency' => $record->frequencyTimes,
            'interval' => $record->frequencyInterval,
            'duration' => $record->durationValue,
            'duration_unit' => $record->durationUnit,
            'allergy_result' => $record->allergyCheckResult,
            'signed_at' => $prescription->signedAt?->format('Y-m-d H:i:s.uP'),
        ];
        if ($prescription->status === PrescriptionStatus::Draft) {
            $this->execute(
                'INSERT INTO prescriptions (prescription_id, local_patient_id, care_episode_id, medication_id, prescribed_by_doctor_profile_id, signed_by_doctor_profile_id, dose_amount, dose_unit, route, frequency_times, frequency_interval, duration_value, duration_unit, status, allergy_check_result, signed_at)
                 VALUES (:id, :patient, :episode, :medication, :prescriber, :signer, :dose, :unit, :route, :frequency, :interval, :duration, :duration_unit, :status, :allergy_result, :signed_at)',
                $fields + ['status' => PrescriptionStatus::Draft->value],
                'No se confirmó el guardado de la prescripción.',
            );
            return;
        }
        $updated = $this->execute(
            "UPDATE prescriptions SET signed_by_doctor_profile_id = :signer, status = :status, allergy_check_result = :allergy_result, signed_at = :signed_at, updated_at = CURRENT_TIMESTAMP
             WHERE prescription_id = :id AND status = 'draft'",
            [
                'signer' => $fields['signer'],
                'status' => PrescriptionStatus::Signed->value,
                'allergy_result' => $fields['allergy_result'],
                'signed_at' => $fields['signed_at'],
                'id' => $fields['id'],
            ],
            'No se confirmó la firma de la prescripción.',
        );
        if ($updated !== 1) {
            throw new PersistenceFailure('No se confirmó la firma de la prescripción.');
        }
    }
    public function findById(string $prescriptionId): ?PrescriptionRecord
    {
        if ($this->postgres && preg_match(self::UUID, $prescriptionId) !== 1) {
            return null;
        }
        $rows = $this->query(
            $this->selectSql().' WHERE p.prescription_id = :id',
            ['id' => $prescriptionId],
            'No se pudo consultar la prescripción.',
        );
        $row = $rows->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }
    public function forPatient(string $patientId): array
    {
        $rows = $this->query(
            $this->selectSql().' WHERE p.local_patient_id = :patient ORDER BY p.created_at, p.prescription_id',
            ['patient' => $patientId],
            'No se pudieron consultar las prescripciones del paciente.',
        );
        return array_map($this->hydrate(...), $rows->fetchAll(PDO::FETCH_ASSOC));
    }
    private function selectSql(): string
    {
        return 'SELECT p.prescription_id, p.local_patient_id, p.care_episode_id, p.medication_id,
                       p.prescribed_by_doctor_profile_id, p.signed_by_doctor_profile_id, p.dose_amount,
                       p.dose_unit, p.route, p.frequency_times, p.frequency_interval, p.duration_value,
                       p.duration_unit, p.status, p.allergy_check_result, p.signed_at,
                       m.code, m.name, m.active_ingredient, m.pharmacological_group, m.validity
                FROM prescriptions p JOIN medications m ON m.medication_id = p.medication_id';
    }
    private function hydrate(array $row): PrescriptionRecord
    {
        $medication = new MedicationSnapshot(
            (string) $row['medication_id'],
            (string) $row['code'],
            (string) $row['name'],
            (string) $row['active_ingredient'],
            (string) $row['pharmacological_group'],
            $row['validity'] === 'active',
        );
        $prescription = Prescription::createDraft(
            (string) $row['prescription_id'],
            (string) $row['local_patient_id'],
            (string) $row['prescribed_by_doctor_profile_id'],
            (string) $row['care_episode_id'],
            $medication,
            PrescriptionRoute::fromInput((string) $row['route']),
            (string) $row['dose_amount'].' '.(string) $row['dose_unit'],
            (string) $row['frequency_times'],
            (string) $row['duration_value'],
        );
        if ($row['status'] === PrescriptionStatus::Signed->value) {
            $prescription = $prescription->sign(
                (string) $row['signed_by_doctor_profile_id'],
                new DateTimeImmutable((string) $row['signed_at']),
                [],
            );
        }
        $allergyResult = (string) $row['allergy_check_result'];
        return new PrescriptionRecord(
            $prescription,
            $allergyResult,
            (string) $row['dose_amount'],
            (string) $row['dose_unit'],
            (int) $row['frequency_times'],
            $row['frequency_interval'] !== null ? (string) $row['frequency_interval'] : null,
            (int) $row['duration_value'],
            (string) $row['duration_unit'],
            $allergyResult === 'warning' ? ['ALLERGY_CONFLICT'] : [],
        );
    }
    private function query(string $sql, array $parameters, string $message): \PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
            return $statement;
        } catch (PDOException $error) {
            throw new PersistenceFailure($message, $error);
        }
    }
    private function execute(string $sql, array $parameters, string $message): int
    {
        $statement = $this->query($sql, $parameters, $message);
        return $statement->rowCount();
    }
}