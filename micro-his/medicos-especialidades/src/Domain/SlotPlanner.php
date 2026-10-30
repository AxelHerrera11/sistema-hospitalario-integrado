<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Domain;

/** Calcula las franjas libres de un día: horario partido en bloques menos los intervalos ocupados (RF-MED-06). */
final class SlotPlanner
{
    /**
     * Un bloque [t, t + minutos) queda libre solo si ningún intervalo ocupado [inicio, fin) lo solapa,
     * aunque la cita no empiece justo en la rejilla (D-02).
     *
     * @param list<array{0: string, 1: string}> $windows
     * @param list<array{0: string, 1: string}> $busy
     * @return list<string>
     */
    public function freeSlots(array $windows, array $busy, int $minutes = 30): array
    {
        $minutes > 0 || throw new \InvalidArgumentException('SLOT_MINUTES_MUST_BE_POSITIVE');
        $occupied = array_map(static fn (array $b): array => [self::minute($b[0]), self::minute($b[1])], $busy);
        $free = [];
        foreach ($windows as [$start, $end]) {
            for ($t = self::minute($start); $t + $minutes <= self::minute($end); $t += $minutes) {
                $taken = array_filter($occupied, static fn (array $b): bool => $b[0] < $t + $minutes && $t < $b[1]);
                $taken === [] && $free[] = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
            }
        }
        return $free;
    }

    private static function minute(string $clock): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $clock));
        return $hours * 60 + $minutes;
    }
}
