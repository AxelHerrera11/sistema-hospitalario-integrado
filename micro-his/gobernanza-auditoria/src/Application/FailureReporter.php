<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

/**
 * Canal secundario (log de la aplicación) para fallos que no deben
 * propagarse al cliente. Recibe un código estable y contexto sin secretos.
 * Las implementaciones no deben lanzar excepciones.
 */
interface FailureReporter
{
    /** @param array<string, scalar|null> $context */
    public function report(string $code, array $context = []): void;
}
