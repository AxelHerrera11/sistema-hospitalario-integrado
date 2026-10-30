<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

use MicroHis\ExpedienteMedico\Domain\CareEpisode;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;

/**
 * Repository del expediente, definido en el límite de Application (DIP): los casos de uso dependen de esta interfaz
 * y los adaptadores PDO e InMemory la implementan. Escribe solo en medical_records; care_episodes es de solo lectura.
 */
interface MedicalRecordRepository
{
    /** @throws PersistenceFailure */
    public function findByPatient(string $patientId): ?MedicalRecord;

    /**
     * Guarda el expediente nuevo y ejecuta $confirm(MedicalRecord) dentro de la misma transacción: si $confirm lanza,
     * el INSERT se revierte. Si otro proceso ya abrió el expediente del paciente (UNIQUE local_patient_id), devuelve
     * ese expediente sin ejecutar $confirm.
     *
     * @param callable(MedicalRecord): void $confirm
     * @throws PersistenceFailure
     */
    public function open(MedicalRecord $record, callable $confirm): MedicalRecord;

    /**
     * @return list<CareEpisode>
     * @throws PersistenceFailure
     */
    public function episodesOf(string $patientId): array;
}
