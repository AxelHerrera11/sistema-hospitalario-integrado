<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use InvalidArgumentException;

/** La solicitud no se puede construir: ningún dato se lee ni se escribe (RF-TA-01, RF-TA-02). */
final class SolicitudInvalida extends InvalidArgumentException
{
    public function __construct(public readonly string $campo)
    {
        parent::__construct("Solicitud inválida: {$campo}.");
    }
}
