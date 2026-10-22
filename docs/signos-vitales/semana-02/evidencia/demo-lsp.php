<?php

declare(strict_types=1);

/*
 * ASII-13 · Semana 2 · Evidencia verificable de LSP. PHP 8.2+, sin framework ni BD; datos ficticios.
 * Ejecutar: php docs/signos-vitales/semana-02/evidencia/demo-lsp.php
 * Umbrales: perfil ilustrativo adulto-2026.1 (Anexo B de la Semana 1), pendiente de validación clínica.
 */

// ANTES: subclases que no respetan el contrato de la clase base.
// Contrato de ThresholdRuleAntes::evaluate(): devuelve null, 'warning' o 'critical'; si falta el signo, null.
abstract class ThresholdRuleAntes { abstract public function evaluate(array $vs): ?string; }

class HeartRateRuleAntes extends ThresholdRuleAntes
{
    public function evaluate(array $vs): ?string
    {
        $v = $vs['heart_rate'] ?? null;
        return $v === null ? null : ($v <= 40 || $v >= 131 ? 'critical' : ($v <= 50 || $v >= 91 ? 'warning' : null));
    }
}
class GlucoseRuleAntes extends ThresholdRuleAntes // refuerza la precondición: exige glucosa
{
    public function evaluate(array $vs): ?string
    {
        $v = $vs['glucose'] ?? throw new InvalidArgumentException('glucosa requerida');
        return $v < 54 || $v >= 400 ? 'critical' : ($v <= 69 || $v >= 181 ? 'warning' : null);
    }
}
class WeightRuleAntes extends ThresholdRuleAntes // rechaza la herencia: el peso no genera alertas
{
    public function evaluate(array $vs): ?string { throw new LogicException('el peso no se evalúa'); }
}

final class ThresholdEvaluatorAntes // compensa con instanceof: conoce a cada subclase
{
    /** @param list<ThresholdRuleAntes> $rules */
    public function __construct(private array $rules) {}
    public function evaluate(array $vs): string
    {
        $level = 'none';
        foreach ($this->rules as $rule) {
            if ($rule instanceof WeightRuleAntes) { continue; }
            if ($rule instanceof GlucoseRuleAntes && !isset($vs['glucose'])) { continue; }
            $s = $rule->evaluate($vs);
            $level = $s === 'critical' || $level === 'critical' ? 'critical' : ($s ?? $level);
        }
        return $level;
    }
}

// DESPUÉS: contrato explícito que toda regla cumple; el evaluador no conoce subclases.
enum Severity: int { case None = 0; case Warning = 1; case Critical = 2; }

interface VitalSignRule
{
    /** Contrato: nunca lanza por un signo ausente (devuelve None) y siempre devuelve un Severity. */
    public function evaluate(array $vs): Severity;
}

abstract class RangeRule implements VitalSignRule
{
    public function __construct(private string $sign, private float $critLow, private float $warnLow, private float $warnHigh, private float $critHigh) {}
    final public function evaluate(array $vs): Severity // plantilla fija: las subclases solo aportan bandas
    {
        $v = $vs[$this->sign] ?? null;
        return match (true) {
            $v === null => Severity::None,
            $v <= $this->critLow || $v >= $this->critHigh => Severity::Critical,
            $v <= $this->warnLow || $v >= $this->warnHigh => Severity::Warning,
            default => Severity::None,
        };
    }
}
final class HeartRateRule extends RangeRule { public function __construct() { parent::__construct('heart_rate', 40, 50, 91, 131); } }
final class OxygenSaturationRule extends RangeRule { public function __construct() { parent::__construct('oxygen_saturation', 91, 95, INF, INF); } }
final class GlucoseRule extends RangeRule { public function __construct() { parent::__construct('glucose', 53, 69, 181, 400); } }
final class NoAlertRule implements VitalSignRule { public function evaluate(array $vs): Severity { return Severity::None; } } // peso y talla
final class RespiratoryRateRule extends RangeRule { public function __construct() { parent::__construct('respiratory_rate', 8, 11, 21, 25); } } // regla nueva

final class ThresholdEvaluator
{
    /** @param list<VitalSignRule> $rules */
    public function __construct(private array $rules) {}
    public function evaluate(array $vs): Severity
    {
        $level = Severity::None;
        foreach ($this->rules as $rule) {
            $s = $rule->evaluate($vs);
            $level = $s->value > $level->value ? $s : $level;
        }
        return $level;
    }
}

// Prueba de contrato: la misma prueba se ejecuta contra cada subtipo, a través del tipo base.
function honorsContract(object $rule): bool
{
    try {
        $empty = $rule->evaluate([]);
        $full = $rule->evaluate(['heart_rate' => 80, 'oxygen_saturation' => 98, 'glucose' => 100, 'respiratory_rate' => 16, 'weight' => 70]);
    } catch (Throwable) {
        return false;
    }
    return $rule instanceof VitalSignRule ? $empty === Severity::None && $full instanceof Severity : $empty === null && in_array($full, [null, 'warning', 'critical'], true);
}

function sourceOf(string $c): string
{
    $r = new ReflectionClass($c);
    return implode('', array_slice(file(__FILE__), $r->getStartLine() - 1, $r->getEndLine() - $r->getStartLine() + 1));
}

$failures = 0;
function check(string $label, bool $ok): void { global $failures; echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n"; $failures += $ok ? 0 : 1; }

$antes = new ThresholdEvaluatorAntes([new HeartRateRuleAntes(), new GlucoseRuleAntes(), new WeightRuleAntes()]);
$reglas = [new HeartRateRule(), new OxygenSaturationRule(), new GlucoseRule(), new NoAlertRule()];
$despues = new ThresholdEvaluator($reglas);
$casos = [ // [etiqueta, medición, severidad esperada]
    ['FC 80 y glucosa 100 → sin alerta', ['heart_rate' => 80, 'glucose' => 100], 'none'],
    ['FC 138 → critical', ['heart_rate' => 138], 'critical'],
    ['FC 95 sin glucosa → warning', ['heart_rate' => 95], 'warning'],
    ['glucosa 60 → warning', ['glucose' => 60], 'warning'],
    ['solo peso → sin alerta', ['weight' => 70], 'none'],
];

echo "== Diseño después reproduce el comportamiento del diseño antes ==\n";
foreach ($casos as [$label, $vs, $esperado]) {
    check($label, $antes->evaluate($vs) === $esperado && strtolower($despues->evaluate($vs)->name) === $esperado);
}

echo "\n== LSP: cada subtipo sustituye al tipo base ==\n";
check('antes: GlucoseRuleAntes y WeightRuleAntes incumplen el contrato base', !honorsContract(new GlucoseRuleAntes()) && !honorsContract(new WeightRuleAntes()) && honorsContract(new HeartRateRuleAntes()));
try { (new GlucoseRuleAntes())->evaluate(['heart_rate' => 138]); $perdida = false; } catch (InvalidArgumentException) { $perdida = true; }
check('antes: sin instanceof, la excepción de glucosa ocultaría la FC 138 crítica', $perdida);
check('antes: ThresholdEvaluatorAntes depende de instanceof', str_contains(sourceOf(ThresholdEvaluatorAntes::class), 'instanceof'));
check('después: las 5 reglas cumplen la misma prueba de contrato', count(array_filter([...$reglas, new RespiratoryRateRule()], 'honorsContract')) === 5);
check('después: FC 138 + SpO2 94 → critical (máximo de los hallazgos)', $despues->evaluate(['heart_rate' => 138, 'oxygen_saturation' => 94]) === Severity::Critical);
check('después: ThresholdEvaluator no usa instanceof ni nombra reglas concretas', !preg_match('/instanceof|HeartRate|Glucose|NoAlert/', sourceOf(ThresholdEvaluator::class)));
check('después: RespiratoryRateRule (FR 26) se agrega sin tocar el evaluador → critical', (new ThresholdEvaluator([...$reglas, new RespiratoryRateRule()]))->evaluate(['respiratory_rate' => 26]) === Severity::Critical);

echo "\n" . ($failures === 0 ? 'OK: todas las aserciones pasaron' : "ERROR: {$failures} aserciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
