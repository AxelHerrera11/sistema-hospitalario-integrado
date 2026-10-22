<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Frecuencia cardiaca (lpm), perfil ilustrativo adulto-2026.1. */
final class HeartRateRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('heart_rate', 40, 50, 91, 131);
    }
}
