<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Persistence;

use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use MicroHis\ExpedienteMedico\Domain\CareEpisode;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;

/** Convierte filas de medical_records y care_episodes en objetos de dominio; una fecha ilegible es fallo de persistencia. */
final class RowMapper
{
    /** @param array<string, mixed> $row */
    public static function record(array $row): MedicalRecord
    {
        return new MedicalRecord(
            (string) $row['medical_record_id'],
            (string) $row['local_patient_id'],
            (string) $row['record_number'],
            self::date($row['opened_at']),
            (string) $row['status'],
            $row['closed_at'] === null ? null : self::date($row['closed_at']),
        );
    }

    /** @param array<string, mixed> $row */
    public static function episode(array $row): CareEpisode
    {
        return new CareEpisode(
            (string) $row['care_episode_id'],
            (string) $row['local_patient_id'],
            (string) $row['type'],
            (string) $row['status'],
            self::date($row['started_at']),
            $row['ended_at'] === null ? null : self::date($row['ended_at']),
            (string) $row['reason'],
            $row['service_code'] === null ? null : (string) $row['service_code'],
        );
    }

    private static function date(mixed $value): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new PersistenceFailure('date_unreadable');
        }
    }
}
