<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Domain;

/** Calcula las franjas libres de un día: horario partido en bloques menos las horas ocupadas (RF-MED-06). */
final class SlotPlanner
{
    /**
     * @param list<array{0: string, 1: string}> $windows
     * @param list<string> $busy
     * @return list<string>
     */
    public function freeSlots(array $windows, array $busy, int $minutes = 30): array
    {
        $free = [];
        foreach ($windows as [$start, $end]) {
            for ($t = strtotime($start); $t + $minutes * 60 <= strtotime($end); $t += $minutes * 60) {
                in_array(date('H:i', $t), $busy, true) || $free[] = date('H:i', $t);
            }
        }
        return $free;
    }
}
