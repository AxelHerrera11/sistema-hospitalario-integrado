<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use MicroHis\TrasladosAltas\Domain\LiberacionDeCama;

/** El COMMIT de HOSPITAL respondió correctamente: la liberación y el resultado propio quedaron confirmados. */
final readonly class ExitoConfirmado implements ResultadoOperacion
{
    public function __construct(public LiberacionDeCama $liberacion)
    {
    }

    public function confirmoCambios(): bool
    {
        return true;
    }
}
