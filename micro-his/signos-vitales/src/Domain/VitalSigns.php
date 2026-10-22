<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Medición de signos vitales (RF-SV-01, RF-SV-02). Si se puede construir, es plausible: se rechaza antes de evaluar umbrales. */
final readonly class VitalSigns
{
    /** Límites fisiológicos (mínimo exclusivo, máximo inclusivo) de los CHECK de la migración hospital 000401. */
    public const LIMITS = [
        'temperature' => [25, 45], 'heart_rate' => [0, 300], 'respiratory_rate' => [0, 100], 'systolic_bp' => [0, 300], 'diastolic_bp' => [0, 200],
        'oxygen_saturation' => [0, 100], 'weight' => [0, 1000], 'height' => [0, 300], 'glucose' => [0, 2000],
    ];

    /** @param array<string, float> $values solo los signos medidos */
    public function __construct(public array $values)
    {
        if ($values === [] || array_diff_key($values, self::LIMITS) !== []) {
            throw new VitalSignViolation('VITAL_SIGNS_INVALID', 422);
        }
        foreach ($values as $sign => $value) {
            [$min, $max] = self::LIMITS[$sign];
            if ($value <= $min || $value > $max) {
                throw new VitalSignViolation('VITAL_SIGN_IMPLAUSIBLE', 422);
            }
        }
        if (isset($values['systolic_bp'], $values['diastolic_bp']) && $values['systolic_bp'] < $values['diastolic_bp']) {
            throw new VitalSignViolation('VITAL_SIGN_IMPLAUSIBLE', 422);
        }
    }

    public function get(string $sign): ?float
    {
        return $this->values[$sign] ?? null;
    }
}
