<?php

declare(strict_types=1);

namespace MicroHis\DashboardBase\Application;

use RuntimeException;

/** La fuente de una tarjeta no respondió; el tablero responde 503 en lugar de 500. */
final class FuenteNoDisponible extends RuntimeException
{
}
