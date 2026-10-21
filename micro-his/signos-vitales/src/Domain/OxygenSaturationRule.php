<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Saturación de oxígeno (%), perfil ilustrativo adulto-2026.1. Sin banda alta. */
final class OxygenSaturationRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('oxygen_saturation', 91, 95, INF, INF);
    }
}
