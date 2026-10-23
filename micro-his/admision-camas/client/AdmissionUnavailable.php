<?php

declare(strict_types=1);

namespace MicroHisClient\Admision;

/** Admisión no respondió. Si fue un alta, el resultado es desconocido: verificar la cama antes de repetir. */
final class AdmissionUnavailable extends \RuntimeException
{
}
