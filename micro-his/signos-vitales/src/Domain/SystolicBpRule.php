<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Presión sistólica (mmHg), perfil de referencia adulto-2026.1 (Anexo B). Ilustrativo: sin validación clínica y no apto para uso asistencial. */
final class SystolicBpRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('systolic_bp', 90, 100, 180, 220);
    }
}
