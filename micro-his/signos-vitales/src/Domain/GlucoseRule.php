<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Glucosa capilar (mg/dL), perfil ilustrativo adulto-2026.1: crítica con < 54 (también con decimales), advertencia hasta 69 y desde 181, crítica desde 400. Si no se midió, no bloquea a las demás reglas. */
final class GlucoseRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('glucose', 54, 69, 181, 400, true);
    }
}
