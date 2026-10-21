<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

/** Reloj del servidor: la marca temporal nunca la envía el cliente. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
