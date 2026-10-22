<?php

declare(strict_types=1);

namespace App\GobernanzaAuditoria;

use Illuminate\Support\Facades\Log;
use MicroHis\Gobernanza\Application\FailureReporter;

/** Envía los fallos de auditoría al log de Laravel con contexto escalar, sin secretos. */
final class LaravelLogFailureReporter implements FailureReporter
{
    public function report(string $code, array $context = []): void
    {
        try {
            Log::error('gobernanza.'.$code, array_filter($context, static fn ($v): bool => $v === null || is_scalar($v)));
        } catch (\Throwable) {
            // Un fallo del log no debe reemplazar el error original.
        }
    }
}
