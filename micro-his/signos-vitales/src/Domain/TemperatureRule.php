<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Temperatura (°C), perfil de referencia adulto-2026.1 (Anexo B). Ilustrativo: sin validación clínica y no apto para uso asistencial. */
final class TemperatureRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('temperature', 35.0, 36.0, 38.1, 39.1);
    }
}
