<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use RuntimeException;

/** HOSPITAL rechazó o no pudo ejecutar una sentencia antes del COMMIT: la transacción se puede revertir con certeza. */
class FalloPersistencia extends RuntimeException
{
}
