<?php

namespace App\Services;

/**
 * Evalúa los signos vitales y decide si activan una alerta (RF-VS-02).
 *
 * Área 5: la alerta que se marca aquí (has_alert + alert_details) es local
 * a la medición. La generación de una alerta crítica (critical_alerts) la
 * consume el área 8 a partir de estos datos (contrato #12).
 */
class VitalSignEvaluator
{
    /** Valores de referencia para una medición normal (adulto). */
    private const NORMAL_RANGES = [
        'temperature' => ['min' => 36.0, 'max' => 38.0],
        'heart_rate' => ['min' => 60, 'max' => 100],
        'respiratory_rate' => ['min' => 12, 'max' => 20],
        'systolic_bp' => ['min' => 90, 'max' => 140],
        'diastolic_bp' => ['min' => 60, 'max' => 90],
        'oxygen_saturation' => ['min' => 94, 'max' => null],
        'glucose' => ['min' => 70, 'max' => 180],
    ];

    /**
     * Devuelve ['has_alert' => bool, 'alert_details' => array|null] para una
     * medición. Los detalles registran qué campo y qué valor salieron de rango.
     *
     * @param  array<string, mixed>  $data
     * @return array{has_alert: bool, alert_details: array<int, array{field: string, value: mixed}>|null}
     */
    public function evaluate(array $data): array
    {
        $alerts = [];

        foreach (self::NORMAL_RANGES as $field => $range) {
            if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                continue;
            }

            $value = (float) $data[$field];

            if ($range['min'] !== null && $value < $range['min']) {
                $alerts[] = ['field' => $field, 'value' => $data[$field]];
                continue;
            }

            if ($range['max'] !== null && $value > $range['max']) {
                $alerts[] = ['field' => $field, 'value' => $data[$field]];
            }
        }

        return [
            'has_alert' => ! empty($alerts),
            'alert_details' => empty($alerts) ? null : $alerts,
        ];
    }
}