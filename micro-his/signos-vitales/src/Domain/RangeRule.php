<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Plantilla de reglas por bandas. evaluate() es final: una subclase solo aporta las bandas y no puede reforzar la precondición. */
abstract class RangeRule implements VitalSignRule
{
    public function __construct(private readonly string $sign, private readonly float $critLow, private readonly float $warnLow, private readonly float $warnHigh, private readonly float $critHigh)
    {
    }

    final public function name(): string
    {
        return $this->sign;
    }

    final public function evaluate(VitalSigns $vitals): Severity
    {
        $value = $vitals->get($this->sign);
        return match (true) {
            $value === null => Severity::None,
            $value <= $this->critLow || $value >= $this->critHigh => Severity::Critical,
            $value <= $this->warnLow || $value >= $this->warnHigh => Severity::Warning,
            default => Severity::None,
        };
    }
}
