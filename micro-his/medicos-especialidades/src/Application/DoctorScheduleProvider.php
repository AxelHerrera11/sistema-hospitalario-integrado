<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

/** Horario del médico, leído de `doctor_schedules` (o por configuración en el micro-HIS aislado). */
interface DoctorScheduleProvider
{
    /** @return list<array{0: string, 1: string, 2?: int}>|null franjas [inicio, fin, minutos por bloque opcional] del día, o null si no hay horario */
    public function scheduleFor(string $doctorId, string $date): ?array;

    /**
     * Lo mismo para varios médicos en una sola lectura (RNF-MED-04: sin una consulta por médico).
     *
     * @param list<string> $doctorIds
     * @return array<string, list<array{0: string, 1: string, 2?: int}>|null> id del médico => franjas del día o null; todo id pedido aparece
     */
    public function schedulesFor(array $doctorIds, string $date): array;
}
