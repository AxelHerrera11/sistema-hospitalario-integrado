<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

/** Lectura del módulo Citas: solo horas ocupadas, sin datos del paciente (RNF-MED-03). */
interface AppointmentReader
{
    /** @return list<string> horas HH:MM ocupadas */
    public function busySlots(string $doctorId, string $date): array;
}
