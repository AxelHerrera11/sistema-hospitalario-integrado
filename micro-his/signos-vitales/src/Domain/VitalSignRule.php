<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/**
 * Contrato LSP de la Semana 2 que todo subtipo debe cumplir:
 * nunca lanza excepciones, un signo ausente devuelve Severity::None y siempre devuelve un Severity.
 */
interface VitalSignRule
{
    public function name(): string;

    public function evaluate(VitalSigns $vitals): Severity;
}
