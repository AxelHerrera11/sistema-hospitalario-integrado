<?php

declare(strict_types=1);

namespace App\Rbac;

use Illuminate\Support\Facades\Log;
use MicroHis\Rbac\Domain\DenialRecorder;

/** Adaptador provisional hasta conectar AuditLogger de Cindy. No registra tokens ni cuerpos. */
final class LogDenials implements DenialRecorder
{
    public function record(array $denial): void
    {
        Log::warning('rbac.access_denied', $denial);
    }
}
