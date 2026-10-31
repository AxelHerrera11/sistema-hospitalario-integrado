<?php

declare(strict_types=1);

/**
 * Evidencia de la Semana 7: refactor conceptual de «perfil de umbrales + unidades» en el flujo de captura, evaluación y alerta.
 * ANTES  = el código real del micro-HIS (ApiKernel + RecordVitalSigns con ADULT_AGE y unidades implícitas).
 * DESPUÉS = la propuesta (Unit, Measurement, ThresholdProfile, ProfileCatalog), definida en este archivo; no toca el micro-HIS.
 * Reutiliza sin cambios VitalSigns, las reglas y ThresholdEvaluator. Uso: php docs/signos-vitales/semana-07/evidencia/demo-refactor.php
 */

namespace Refactor {
    use MicroHis\SignosVitales\Domain\Assessment;
    use MicroHis\SignosVitales\Domain\GlucoseRule;
    use MicroHis\SignosVitales\Domain\HeartRateRule;
    use MicroHis\SignosVitales\Domain\NoAlertRule;
    use MicroHis\SignosVitales\Domain\OxygenSaturationRule;
    use MicroHis\SignosVitales\Domain\RespiratoryRateRule;
    use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
    use MicroHis\SignosVitales\Domain\VitalSigns;
    use MicroHis\SignosVitales\Domain\VitalSignViolation;

    /** Medida con su unidad declarada; convierte a la unidad canónica del signo antes de que exista VitalSigns. */
    final readonly class Measurement
    {
        /** Conversión a la unidad canónica (°C y mg/dL, las que asumen los umbrales). Una unidad que no está aquí no se acepta. */
        private const TO_CANONICAL = [
            'temperature' => ['°C' => [1.0, 0.0], '°F' => [5 / 9, -32 * 5 / 9]],
            'glucose' => ['mg/dL' => [1.0, 0.0], 'mmol/L' => [18.016, 0.0]],
        ];
        /** Unidad canónica de los signos que no tienen alternativa: se acepta solo esa. */
        private const FIXED = ['heart_rate' => 'lpm', 'respiratory_rate' => 'rpm', 'oxygen_saturation' => '%', 'weight' => 'kg', 'height' => 'cm', 'systolic_bp' => 'mmHg', 'diastolic_bp' => 'mmHg'];

        public function __construct(public string $sign, public float $value, public ?string $unit = null)
        {
        }

        public function canonical(): float
        {
            if ($this->unit === null) {
                return $this->value; // compatibilidad: un número sin unidad ya es canónico
            }
            $conversion = self::TO_CANONICAL[$this->sign][$this->unit] ?? ((self::FIXED[$this->sign] ?? null) === $this->unit ? [1.0, 0.0] : null);
            [$scale, $offset] = $conversion ?? throw new VitalSignViolation('UNIT_NOT_SUPPORTED', 422);
            return $this->value * $scale + $offset;
        }
    }

    /** Perfil de umbrales con identidad y vigencia: decide a quién aplica y evalúa. El caso de uso ya no conoce edades ni listas de reglas. */
    interface ThresholdProfile
    {
        public function id(): string;

        public function appliesTo(int $age): bool;

        public function evaluate(VitalSigns $vitals): Assessment;
    }

    final class AdultProfile implements ThresholdProfile
    {
        private readonly ThresholdEvaluator $evaluator;

        public function __construct()
        {
            $this->evaluator = new ThresholdEvaluator([new HeartRateRule(), new OxygenSaturationRule(), new GlucoseRule(), new RespiratoryRateRule(), new NoAlertRule('weight'), new NoAlertRule('height')]);
        }

        public function id(): string
        {
            return 'adulto-2026.1';
        }

        public function appliesTo(int $age): bool
        {
            return $age >= 18;
        }

        public function evaluate(VitalSigns $vitals): Assessment
        {
            return $this->evaluator->evaluate($vitals);
        }
    }

    final class ProfileCatalog
    {
        /** @param list<ThresholdProfile> $profiles */
        public function __construct(private readonly array $profiles)
        {
        }

        public function forAge(int $age): ?ThresholdProfile
        {
            foreach ($this->profiles as $profile) {
                if ($profile->appliesTo($age)) {
                    return $profile;
                }
            }
            return null;
        }
    }

    /** Caso de uso después del refactor: normaliza, elige el perfil por el catálogo y evalúa. Sin ADULT_AGE ni lista de reglas. */
    final class RecordVitalSignsAfter
    {
        public function __construct(private readonly ProfileCatalog $profiles)
        {
        }

        /** @param array<string, float|int|array{value: float|int, unit: string}> $raw */
        public function execute(array $raw, int $age): array
        {
            try {
                $values = [];
                foreach ($raw as $sign => $entry) {
                    $m = is_array($entry) ? new Measurement($sign, (float) $entry['value'], $entry['unit']) : new Measurement($sign, (float) $entry);
                    $values[$sign] = $m->canonical();
                }
                $vitals = new VitalSigns($values);
            } catch (VitalSignViolation $e) {
                return ['status' => $e->status, 'code' => $e->getMessage()];
            }
            $profile = $this->profiles->forAge($age);
            $assessment = $profile?->evaluate($vitals);
            return [
                'status' => 201,
                'code' => $assessment === null ? 'THRESHOLDS_NOT_APPLICABLE' : ($assessment->requiresAlert() ? 'ALERT_GENERATED' : 'OK'),
                'severity' => strtolower(($assessment?->severity)?->name ?? 'none'),
                'profile' => $profile?->id(),
                'canonical' => $values,
            ];
        }
    }
}

namespace {
    use MicroHis\SignosVitales\Application\EpisodeContext;
    use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
    use MicroHis\SignosVitales\Persistence\InMemoryPermissionChecker;
    use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
    use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
    use MicroHis\SignosVitales\Presentation\Request;
    use Refactor\AdultProfile;
    use Refactor\ProfileCatalog;
    use Refactor\RecordVitalSignsAfter;

    require __DIR__ . '/../../../../micro-his/signos-vitales/app.php';

    /** ANTES: el código real. Solo admite números en la unidad canónica; la edad decide el perfil dentro del caso de uso. */
    $before = static function (array $body, int $age): string {
        $api = signos_vitales_api(
            new InMemoryCareEpisodeReader(['ep1' => ['dra.lopez' => new EpisodeContext(true, $age)]]),
            new InMemoryVitalSignRepository(),
            new QueuedAlertNotifier(),
            new InMemoryPermissionChecker(['dra.lopez' => ['signos_vitales.registrar']]),
        );
        $r = $api->handle(new Request('POST', '/api/v1/care-episodes/ep1/vital-signs', ['X-User-ID' => 'dra.lopez'], $body + ['measured_at' => '2026-10-14 08:30:00']));
        $json = json_decode($r['body'], true);
        return $r['status'] . ' ' . ($r['status'] === 201 ? $json['code'] . '/' . $json['severity'] : $json['code']);
    };
    $after = new RecordVitalSignsAfter(new ProfileCatalog([new AdultProfile()]));
    $afterText = static function (array $raw, int $age) use ($after): string {
        $r = $after->execute($raw, $age);
        return $r['status'] . ' ' . ($r['status'] === 201 ? $r['code'] . '/' . $r['severity'] : $r['code']);
    };

    $fails = 0;
    $row = static function (string $name, string $antes, string $despues, string $esperadoAntes, string $esperadoDespues) use (&$fails): void {
        $ok = $antes === $esperadoAntes && $despues === $esperadoDespues;
        $ok || $fails++;
        printf("%s  %-46s antes: %-26s después: %s%s", $ok ? 'PASS' : 'FAIL', $name, $antes, $despues, PHP_EOL);
    };

    echo "== Unidades: el cambio práctico de la Semana 6 ==" . PHP_EOL;
    // «antes» recibe el número tal como lo enviaría el cliente del equipo, sin poder declarar la unidad.
    $row('glucosa 5,5 mmol/L (normal)', $before(['glucose' => 5.5], 45), $afterText(['glucose' => ['value' => 5.5, 'unit' => 'mmol/L']], 45), '201 ALERT_GENERATED/critical', '201 OK/none');
    $row('glucosa 12 mmol/L (216 mg/dL, warning)', $before(['glucose' => 12], 45), $afterText(['glucose' => ['value' => 12, 'unit' => 'mmol/L']], 45), '201 ALERT_GENERATED/critical', '201 ALERT_GENERATED/warning');
    $row('glucosa 2,9 mmol/L (52,2 mg/dL, critical)', $before(['glucose' => 2.9], 45), $afterText(['glucose' => ['value' => 2.9, 'unit' => 'mmol/L']], 45), '201 ALERT_GENERATED/critical', '201 ALERT_GENERATED/critical');
    $row('temperatura 98,6 °F', $before(['temperature' => 98.6], 45), $afterText(['temperature' => ['value' => 98.6, 'unit' => '°F']], 45), '422 VITAL_SIGN_IMPLAUSIBLE', '201 OK/none');
    $row('temperatura 122 °F (50 °C, imposible)', $before(['temperature' => 122], 45), $afterText(['temperature' => ['value' => 122, 'unit' => '°F']], 45), '422 VITAL_SIGN_IMPLAUSIBLE', '422 VITAL_SIGN_IMPLAUSIBLE');
    $row('glucosa con unidad que no existe (mg/L)', 'no expresable', $afterText(['glucose' => ['value' => 5.5, 'unit' => 'mg/L']], 45), 'no expresable', '422 UNIT_NOT_SUPPORTED');
    $row('FC en mmol/L', 'no expresable', $afterText(['heart_rate' => ['value' => 80, 'unit' => 'mmol/L']], 45), 'no expresable', '422 UNIT_NOT_SUPPORTED');

    echo PHP_EOL . "== Regresión: con valores canónicos el comportamiento no cambia ==" . PHP_EOL;
    $row('glucosa 5,5 sin unidad (compatibilidad)', $before(['glucose' => 5.5], 45), $afterText(['glucose' => 5.5], 45), '201 ALERT_GENERATED/critical', '201 ALERT_GENERATED/critical');
    $row('FC 138 + SpO2 94', $before(['heart_rate' => 138, 'oxygen_saturation' => 94], 45), $afterText(['heart_rate' => 138, 'oxygen_saturation' => 94], 45), '201 ALERT_GENERATED/critical', '201 ALERT_GENERATED/critical');
    $row('FC 95 (la glucosa ausente no bloquea)', $before(['heart_rate' => 95], 45), $afterText(['heart_rate' => 95], 45), '201 ALERT_GENERATED/warning', '201 ALERT_GENERATED/warning');
    $row('solo peso 70 kg', $before(['weight' => 70], 45), $afterText(['weight' => 70], 45), '201 OK/none', '201 OK/none');
    $row('paciente de 9 años', $before(['heart_rate' => 138], 9), $afterText(['heart_rate' => 138], 9), '201 THRESHOLDS_NOT_APPLICABLE/none', '201 THRESHOLDS_NOT_APPLICABLE/none');
    $row('temperatura 50 °C (imposible)', $before(['temperature' => 50], 45), $afterText(['temperature' => 50], 45), '422 VITAL_SIGN_IMPLAUSIBLE', '422 VITAL_SIGN_IMPLAUSIBLE');

    echo PHP_EOL . "== Lo que gana el después ==" . PHP_EOL;
    $r = $after->execute(['glucose' => ['value' => 2.9, 'unit' => 'mmol/L']], 45);
    $extra = $r['profile'] === 'adulto-2026.1' && abs($r['canonical']['glucose'] - 52.2464) < 0.001;
    $extra || $fails++;
    printf("%s  la respuesta lleva el perfil aplicado (%s) y el valor canónico (%.1f mg/dL)%s", $extra ? 'PASS' : 'FAIL', $r['profile'], $r['canonical']['glucose'], PHP_EOL);
    $pediatrico = new class implements Refactor\ThresholdProfile {
        public function id(): string
        {
            return 'pediatrico-demo';
        }

        public function appliesTo(int $age): bool
        {
            return $age < 18;
        }

        public function evaluate(MicroHis\SignosVitales\Domain\VitalSigns $vitals): MicroHis\SignosVitales\Domain\Assessment
        {
            return new MicroHis\SignosVitales\Domain\Assessment(MicroHis\SignosVitales\Domain\Severity::None, []);
        }
    };
    $conPediatrico = (new RecordVitalSignsAfter(new ProfileCatalog([new AdultProfile(), $pediatrico])))->execute(['heart_rate' => 138], 9);
    $ext = $conPediatrico['code'] === 'OK' && $conPediatrico['profile'] === 'pediatrico-demo';
    $ext || $fails++;
    printf("%s  un perfil nuevo se agrega al catálogo sin modificar el caso de uso (OCP)%s", $ext ? 'PASS' : 'FAIL', PHP_EOL);

    exit($fails === 0 ? 0 : 1);
}
