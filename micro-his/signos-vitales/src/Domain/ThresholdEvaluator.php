<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Evalúa todas las reglas a través del tipo base (RF-SV-03, RF-SV-04). No usa instanceof ni conoce reglas concretas. */
final class ThresholdEvaluator
{
    /** @param list<VitalSignRule> $rules */
    public function __construct(private readonly array $rules)
    {
    }

    public function evaluate(VitalSigns $vitals): Assessment
    {
        $severity = Severity::None;
        $findings = [];
        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($vitals);
            $severity = $severity->max($result);
            $result === Severity::None || $findings[$rule->name()] = $result;
        }
        return new Assessment($severity, $findings);
    }
}
