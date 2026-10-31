<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/** Reloj del servidor: la fecha de apertura nunca la envía el cliente. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
