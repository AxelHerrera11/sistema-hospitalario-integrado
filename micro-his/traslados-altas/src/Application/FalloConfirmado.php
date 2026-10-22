<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

/** HOSPITAL falló antes del COMMIT o informó que no confirmó: se sabe que no quedó ningún cambio. */
final readonly class FalloConfirmado implements ResultadoOperacion
{
    public function __construct(public string $codigo)
    {
    }

    public function confirmoCambios(): bool
    {
        return false;
    }
}
