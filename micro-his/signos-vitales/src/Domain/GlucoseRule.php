<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Glucosa capilar (mg/dL), perfil ilustrativo adulto-2026.1. Si no se midió, no bloquea a las demás reglas. */
final class GlucoseRule extends RangeRule
{
    public function __construct()
    {
        parent::__construct('glucose', 53, 69, 181, 400);
    }
}
