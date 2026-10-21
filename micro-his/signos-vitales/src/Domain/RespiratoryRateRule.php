<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Frecuencia respiratoria (rpm), perfil ilustrativo adulto-2026.1. */
final class RespiratoryRateRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('respiratory_rate', 8, 11, 21, 25);
    }
}
