<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Integration;

use MicroHis\Gobernanza\Application\FailureReporter;

/** Reporta al log de PHP una línea JSON con código y contexto escalar. */
final class ErrorLogFailureReporter implements FailureReporter
{
    public function report(string $code, array $context = []): void
    {
        try {
            $safe = array_filter($context, static fn ($v): bool => $v === null || is_scalar($v));
            error_log('[gobernanza] ' . json_encode(['code' => $code] + $safe, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } catch (\Throwable) {
            // Un fallo del log no debe ocultar ni reemplazar el error original.
        }
    }
}
