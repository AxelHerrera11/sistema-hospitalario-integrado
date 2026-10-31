<?php

declare(strict_types=1);

namespace MicroHis\DashboardBase\Application;

/** Puerto de lectura hacia el contexto de citas (ASII-05): el tablero compone, no es dueño de los datos. */
interface CitasDelDia
{
    /**
     * @return array<string, int> conteo por estado (`scheduled`, `confirmed`, `completed`, `cancelled`)
     * @throws FuenteNoDisponible
     */
    public function porEstado(string $fecha): array;
}
