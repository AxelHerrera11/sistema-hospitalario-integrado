<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Persistence;

use MicroHis\SignosVitales\Application\AlertNotifier;
use MicroHis\SignosVitales\Domain\Assessment;

/** Adaptador de salida en memoria mientras no se acuerde el canal real (C3). Deja la alerta en una cola. */
final class QueuedAlertNotifier implements AlertNotifier
{
    /** @var list<array{vital_sign_id: string, severity: string}> */
    public array $queue = [];

    public function notify(string $vitalSignId, Assessment $assessment): void
    {
        $this->queue[] = ['vital_sign_id' => $vitalSignId, 'severity' => strtolower($assessment->severity->name)];
    }
}
