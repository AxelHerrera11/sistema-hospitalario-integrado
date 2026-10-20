<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Presión diastólica (mmHg), perfil de referencia adulto-2026.1 (Anexo B). Solo banda alta. Ilustrativo: sin validación clínica y no apto para uso asistencial. */
final class DiastolicBpRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('diastolic_bp', -INF, -INF, 110, 120);
    }
}
