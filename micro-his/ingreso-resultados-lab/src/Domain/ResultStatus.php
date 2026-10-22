<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Domain;

enum ResultStatus: string
{
    case Pending = 'pendiente';
    case Captured = 'capturado';
    case Corrected = 'corregido';
}
