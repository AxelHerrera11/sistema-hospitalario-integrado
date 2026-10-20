<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\DiastolicBpRule;
use MicroHis\SignosVitales\Domain\GlucoseRule;
use MicroHis\SignosVitales\Domain\HeartRateRule;
use MicroHis\SignosVitales\Domain\NoAlertRule;
use MicroHis\SignosVitales\Domain\OxygenSaturationRule;
use MicroHis\SignosVitales\Domain\RespiratoryRateRule;
use MicroHis\SignosVitales\Domain\Severity;
use MicroHis\SignosVitales\Domain\SystolicBpRule;
use MicroHis\SignosVitales\Domain\TemperatureRule;
use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
use MicroHis\SignosVitales\Domain\Uuid;
use MicroHis\SignosVitales\Domain\VitalSignRule;
use MicroHis\SignosVitales\Domain\VitalSigns;
use MicroHis\SignosVitales\Domain\VitalSignViolation;

// Pruebas unitarias del dominio: una clase por vez, sin adaptadores, sin API y sin episodios.
require __DIR__ . '/support.php';

/** Código y estado de la violación que lanza construir `VitalSigns`, o «ok». */
$violacion = static function (array $signos): string {
    try {
        new VitalSigns($signos);
        return 'ok';
    } catch (VitalSignViolation $e) {
        return $e->getMessage() . ' ' . $e->status;
    }
};
$sev = static fn (VitalSignRule $regla, array $signos): Severity => $regla->evaluate(new VitalSigns($signos));

check('U-01 RF-SV-02: sin signos o con un signo desconocido -> VITAL_SIGNS_INVALID 422', $violacion([]) === 'VITAL_SIGNS_INVALID 422' && $violacion(['pulso' => 80.0]) === 'VITAL_SIGNS_INVALID 422');
$limites = ['temperature' => [25.0, 45.0], 'heart_rate' => [0.0, 300.0], 'oxygen_saturation' => [0.0, 100.0], 'glucose' => [0.0, 2000.0]];
$ok = true;
foreach ($limites as $signo => [$min, $max]) {
    $ok = $ok && $violacion([$signo => $min]) === 'VITAL_SIGN_IMPLAUSIBLE 422' && $violacion([$signo => $min + 0.1]) === 'ok' && $violacion([$signo => $max]) === 'ok' && $violacion([$signo => $max + 0.1]) === 'VITAL_SIGN_IMPLAUSIBLE 422';
}
check('U-02 RF-SV-02: mínimo exclusivo y máximo inclusivo de temperatura, FC, SpO2 y glucosa', $ok);
check('U-03 RF-SV-02: sistólica < diastólica es imposible; igual o un solo valor es posible', $violacion(['systolic_bp' => 80.0, 'diastolic_bp' => 90.0]) === 'VITAL_SIGN_IMPLAUSIBLE 422' && $violacion(['systolic_bp' => 90.0, 'diastolic_bp' => 90.0]) === 'ok' && $violacion(['systolic_bp' => 120.0]) === 'ok');
$bandas = [40 => Severity::Critical, 41 => Severity::Warning, 50 => Severity::Warning, 51 => Severity::None, 90 => Severity::None, 91 => Severity::Warning, 130 => Severity::Warning, 131 => Severity::Critical];
$ok = true;
foreach ($bandas as $fc => $esperada) {
    $ok = $ok && $sev(new HeartRateRule(), ['heart_rate' => (float) $fc]) === $esperada;
}
check('U-04 RF-SV-03: bandas de la FC en cada límite (40, 41, 50, 51, 90, 91, 130, 131)', $ok);
check('U-05 RF-SV-03: SpO2 91 critical, 92 y 95 warning, 96 y 100 sin alerta (sin banda alta)', array_map(static fn (int $v): Severity => $sev(new OxygenSaturationRule(), ['oxygen_saturation' => (float) $v]), [91, 92, 95, 96, 100]) === [Severity::Critical, Severity::Warning, Severity::Warning, Severity::None, Severity::None]);
check('U-06 RF-SV-03: bandas de la glucosa en mg/dL (53, 54, 69, 70, 180, 181, 399, 400)', array_map(static fn (int $v): Severity => $sev(new GlucoseRule(), ['glucose' => (float) $v]), [53, 54, 69, 70, 180, 181, 399, 400])
    === [Severity::Critical, Severity::Warning, Severity::Warning, Severity::None, Severity::None, Severity::Warning, Severity::Warning, Severity::Critical]);
check('U-07 RF-SV-05: peso y talla nunca generan alerta, ni en el límite', $sev(new NoAlertRule('weight'), ['weight' => 1000.0]) === Severity::None && $sev(new NoAlertRule('height'), ['height' => 300.0]) === Severity::None);
$reglas = [new TemperatureRule(), new HeartRateRule(), new RespiratoryRateRule(), new SystolicBpRule(), new DiastolicBpRule(), new OxygenSaturationRule(), new GlucoseRule(), new NoAlertRule('weight'), new NoAlertRule('height')];
check('U-08 RNF-SV-02 (LSP): ninguna de las nueve reglas lanza ni alerta ante una medición sin su signo', count(array_filter($reglas, static fn (VitalSignRule $r): bool => $r->evaluate(new VitalSigns(['weight' => 70.0])) === Severity::None)) === 9);
$a = (new ThresholdEvaluator($reglas))->evaluate(new VitalSigns(['heart_rate' => 138.0, 'oxygen_saturation' => 94.0, 'glucose' => 100.0]));
check('U-09 RF-SV-04: la severidad es el máximo y los hallazgos solo los signos fuera de umbral', $a->severity === Severity::Critical && array_keys($a->findings) === ['heart_rate', 'oxygen_saturation']);
$n = (new ThresholdEvaluator($reglas))->evaluate(new VitalSigns(['heart_rate' => 80.0, 'weight' => 70.0]));
check('U-10 RF-SV-04: sin valores fuera de umbral, severidad None y sin hallazgos', $n->severity === Severity::None && $n->findings === [] && !$n->requiresAlert());
check('U-11 RF-SV-04: Assessment.requiresAlert es verdadero con Warning y con Critical', (new Assessment(Severity::Warning, ['heart_rate' => Severity::Warning]))->requiresAlert() && (new Assessment(Severity::Critical, []))->requiresAlert());
check('U-12 RF-SV-04: Severity.max conserva el mayor en cualquier orden', Severity::None->max(Severity::Warning) === Severity::Warning && Severity::Critical->max(Severity::Warning) === Severity::Critical && Severity::None->max(Severity::None) === Severity::None);
check('U-13 Unidades: las reglas de glucosa asumen mg/dL; 5,5 se lee como 5,5 mg/dL (critical) y no existe una unidad alternativa', $sev(new GlucoseRule(), ['glucose' => 5.5]) === Severity::Critical && !is_file(__DIR__ . '/../src/Domain/Unit.php'));
check('U-14 Uuid: formato v4 y dos valores distintos', preg_match(Uuid::PATTERN, $u1 = Uuid::v4()) === 1 && $u1 !== Uuid::v4());
$bandasTemp = [[35.0, Severity::Critical], [35.1, Severity::Warning], [36.0, Severity::Warning], [36.1, Severity::None], [38.0, Severity::None], [38.1, Severity::Warning], [39.0, Severity::Warning], [39.1, Severity::Critical]];
$ok = true;
foreach ($bandasTemp as [$t, $esperada]) {
    $ok = $ok && $sev(new TemperatureRule(), ['temperature' => (float) $t]) === $esperada;
}
check('U-15 RF-SV-03: bandas de temperatura en cada límite del Anexo B (35, 35,1, 36, 36,1, 38, 38,1, 39, 39,1)', $ok);
check('U-16 RF-SV-03: bandas de presión sistólica (90, 91, 100, 101, 179, 180, 219, 220) y diastólica (109, 110, 119, 120)', array_map(static fn (int $v): Severity => $sev(new SystolicBpRule(), ['systolic_bp' => (float) $v]), [90, 91, 100, 101, 179, 180, 219, 220])
    === [Severity::Critical, Severity::Warning, Severity::Warning, Severity::None, Severity::None, Severity::Warning, Severity::Warning, Severity::Critical]
    && array_map(static fn (int $v): Severity => $sev(new DiastolicBpRule(), ['diastolic_bp' => (float) $v]), [109, 110, 119, 120]) === [Severity::None, Severity::Warning, Severity::Warning, Severity::Critical]);
check('U-17 RF-SV-03: la glucosa con decimales es crítica por debajo de 54 (53,5 y 53,99) y warning desde 54', $sev(new GlucoseRule(), ['glucose' => 53.5]) === Severity::Critical && $sev(new GlucoseRule(), ['glucose' => 53.99]) === Severity::Critical && $sev(new GlucoseRule(), ['glucose' => 54.0]) === Severity::Warning);
defecto('D-06','U-13: una unidad distinta de mg/dL no se puede declarar (glucosa 5,5 mmol/L da una alerta crítica falsa)', is_file(__DIR__ . '/../src/Domain/Unit.php'));
resumen('Unitarias');
