<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\DoctorScheduleProvider;
use PDO;

/**
 * Horario semanal leído de `doctor_schedules` (migración 000900, mantenida por ASII-09). Solo lectura.
 * Las franjas se parten en bloques fijos de `SlotPlanner`: `slot_minutes` por horario queda pendiente (issue #207).
 */
final class PdoScheduleProvider implements DoctorScheduleProvider
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function scheduleFor(string $doctorId, string $date): ?array
    {
        $vigente = "doctor_profile_id = ? AND status = 'active' AND valid_from <= ? AND (valid_to IS NULL OR valid_to > ?)";
        if ($this->rows("SELECT 1 FROM doctor_schedules WHERE $vigente", [$doctorId, $date, $date], PDO::FETCH_COLUMN) === []) {
            return null;
        }
        $weekday = (int) date('N', strtotime($date));
        $windows = $this->rows("SELECT start_minute, end_minute FROM doctor_schedules WHERE $vigente AND weekday = ? ORDER BY start_minute", [$doctorId, $date, $date, $weekday]);
        return array_map(static fn (array $w): array => [self::clock((int) $w['start_minute']), self::clock((int) $w['end_minute'])], $windows);
    }

    private static function clock(int $minute): string
    {
        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }

    private function rows(string $sql, array $params, int $mode = PDO::FETCH_ASSOC): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll($mode);
    }
}
