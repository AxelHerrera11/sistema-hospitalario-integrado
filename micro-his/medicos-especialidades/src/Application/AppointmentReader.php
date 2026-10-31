<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

/** Lectura del módulo Citas: solo intervalos ocupados, sin datos del paciente (RNF-MED-03). */
interface AppointmentReader
{
    /** @return list<array{0: string, 1: string}> intervalos [inicio, fin) HH:MM ocupados; un fin posterior al día se informa como 24:00 */
    public function busyIntervals(string $doctorId, string $date): array;

    /**
     * Lo mismo para varios médicos en una sola lectura (RNF-MED-04: sin una consulta por médico).
     *
     * @param list<string> $doctorIds
     * @return array<string, list<array{0: string, 1: string}>> id del médico => intervalos; todo id pedido aparece, vacío si no tiene citas
     */
    public function busyIntervalsFor(array $doctorIds, string $date): array;
}
