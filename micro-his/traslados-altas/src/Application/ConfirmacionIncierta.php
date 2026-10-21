<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use RuntimeException;

/** El COMMIT se envió y no hubo respuesta fiable; nadie sabe si se confirmó. No se debe reintentar a ciegas. */
final class ConfirmacionIncierta extends RuntimeException
{
}
