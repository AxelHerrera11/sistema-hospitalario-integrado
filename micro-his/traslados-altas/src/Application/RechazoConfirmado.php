<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use MicroHis\TrasladosAltas\Domain\Rechazo;

/** Una regla o la autorización rechazaron la operación; la transacción se revirtió o ni siquiera se abrió. */
final readonly class RechazoConfirmado implements ResultadoOperacion
{
    public function __construct(public Rechazo $motivo)
    {
    }

    public function confirmoCambios(): bool
    {
        return false;
    }
}
