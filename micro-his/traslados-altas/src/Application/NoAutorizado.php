<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use RuntimeException;

/** El actor no puede realizar la consulta; no se abrió ninguna transacción. */
final class NoAutorizado extends RuntimeException
{
}
