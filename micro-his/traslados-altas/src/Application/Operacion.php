<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

/** Operación que se autoriza. Su correspondencia con permisos RBAC se acuerda con ASII-02 al integrar (RF-TA-03). */
enum Operacion: string
{
    case Traslado = 'traslado';
    case Alta = 'alta';
    // Lectura del estado, p. ej. para resolver un ResultadoIncierto antes de reintentar (RNF-TA-06).
    case Consulta = 'consulta';
}
