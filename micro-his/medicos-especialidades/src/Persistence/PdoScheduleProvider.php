<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\DoctorScheduleProvider;
use PDO;

/**
 * Horario semanal leído de `doctor_schedules` (migración 000900, mantenida por ASII-09). Solo lectura.
 * Cada franja informa su `slot_minutes`, con el que `SlotPlanner` parte el horario en bloques.
 */
final class PdoScheduleProvider implements DoctorScheduleProvider
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function scheduleFor(string $doctorId, string $date): ?array
    {
        return $this->schedulesFor([$doctorId], $date)[$doctorId];
    }

    /** Una sola consulta para todos los médicos: sin filas vigentes -> null; con filas pero ninguna ese día de la semana -> []. */
    public function schedulesFor(array $doctorIds, string $date): array
    {
        $result = array_fill_keys($doctorIds, null);
        if ($doctorIds === []) {
            return $result;
        }
        $marks = implode(', ', array_fill(0, count($doctorIds), '?'));
        $rows = $this->rows("SELECT doctor_profile_id, weekday, start_minute, end_minute, slot_minutes FROM doctor_schedules
            WHERE doctor_profile_id IN ($marks) AND status = 'active' AND valid_from <= ? AND (valid_to IS NULL OR valid_to > ?) ORDER BY start_minute",
            [...array_values($doctorIds), $date, $date]);
        $weekday = (int) date('N', strtotime($date));
        foreach ($rows as $w) {
            $result[$w['doctor_profile_id']] ??= [];
            if ((int) $w['weekday'] === $weekday) {
                $result[$w['doctor_profile_id']][] = [self::clock((int) $w['start_minute']), self::clock((int) $w['end_minute']), (int) $w['slot_minutes']];
            }
        }
        return $result;
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
