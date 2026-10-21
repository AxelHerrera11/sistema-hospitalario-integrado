<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

use MicroHis\SignosVitales\Domain\Assessment;

/** Canal de notificación de alertas (acuerdo C3). Se llama solo después del COMMIT. */
interface AlertNotifier
{
    public function notify(string $vitalSignId, Assessment $assessment): void;
}
