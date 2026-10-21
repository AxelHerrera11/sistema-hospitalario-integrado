<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Signos sin umbral, como peso y talla (RF-SV-05): cumplen el contrato devolviendo siempre None, en lugar de lanzar. */
final class NoAlertRule implements VitalSignRule
{
    public function __construct(private readonly string $sign)
    {
    }

    public function name(): string
    {
        return $this->sign;
    }

    public function evaluate(VitalSigns $vitals): Severity
    {
        return Severity::None;
    }
}
