<?php

declare(strict_types=1);

/*
 * ASII-12 · Semana 2 · Evidencia verificable de OCP.
 *
 * Ejecutar: php docs/alergias/semana-02/evidencia/demo-ocp.php
 *
 * Compara el diseño "antes" (switch en el servicio) con el diseño "después"
 * (reglas que implementan AllergyAlertRule) y demuestra que una regla nueva
 * (LatexAllergyRule) cambia la alerta sin modificar AllergyAlertEvaluator.
 * PHP 8.2+, sin framework ni base de datos: los datos son ficticios.
 */

enum AlertLevel: int
{
    case Neutral = 0;      // «Sin alergias registradas»
    case Amber = 1;        // leves, moderadas, desconocidas o intolerancias
    case Red = 2;          // severe o life_threatening (no descartable)
    case Unavailable = 3;  // falla segura: «Alergias no disponibles»
}

final readonly class Allergy
{
    public function __construct(
        public string $allergen,
        public string $type,      // allergy | intolerance | unknown
        public string $severity,  // mild | moderate | severe | life_threatening | unknown
        public string $status = 'active',
    ) {
    }
}

final readonly class AlertResult
{
    /** @param list<Allergy> $triggers */
    public function __construct(public AlertLevel $level, public array $triggers = [])
    {
    }
}

// ---------------------------------------------------------------------------
// ANTES: una sola clase decide el banner con un switch. Cada regla clínica
// nueva (látex, anafilaxia previa, alérgenos de quirófano…) obliga a editarla.
// ---------------------------------------------------------------------------
final class AllergyAlertServiceAntes
{
    /** @param list<Allergy>|null $allergies */
    public function nivel(?array $allergies): AlertLevel
    {
        if ($allergies === null) {
            return AlertLevel::Unavailable;
        }
        $level = AlertLevel::Neutral;
        foreach ($allergies as $allergy) {
            if ($allergy->status !== 'active') {
                continue;
            }
            switch ($allergy->severity) {
                case 'severe':
                case 'life_threatening':
                    return AlertLevel::Red;
                default:
                    $level = AlertLevel::Amber;
            }
        }

        return $level;
    }
}

// ---------------------------------------------------------------------------
// DESPUÉS: abierto a extensión (reglas nuevas), cerrado a modificación.
// ---------------------------------------------------------------------------
interface AllergyAlertRule
{
    public function appliesTo(Allergy $allergy): bool;

    public function level(): AlertLevel;
}

final class SevereAllergyRule implements AllergyAlertRule
{
    public function appliesTo(Allergy $allergy): bool
    {
        return in_array($allergy->severity, ['severe', 'life_threatening'], true);
    }

    public function level(): AlertLevel
    {
        return AlertLevel::Red;
    }
}

final class NonSevereAllergyRule implements AllergyAlertRule
{
    public function appliesTo(Allergy $allergy): bool
    {
        return in_array($allergy->severity, ['mild', 'moderate', 'unknown'], true);
    }

    public function level(): AlertLevel
    {
        return AlertLevel::Amber;
    }
}

final class AllergyAlertEvaluator
{
    /** @param iterable<AllergyAlertRule> $rules */
    public function __construct(private readonly iterable $rules)
    {
    }

    /** @param list<Allergy>|null $allergies null = no se pudieron leer */
    public function evaluate(?array $allergies): AlertResult
    {
        if ($allergies === null) {
            return new AlertResult(AlertLevel::Unavailable);
        }
        $level = AlertLevel::Neutral;
        $triggers = [];
        foreach ($allergies as $allergy) {
            if ($allergy->status !== 'active') {
                continue;
            }
            foreach ($this->rules as $rule) {
                if ($rule->appliesTo($allergy) && $rule->level()->value >= $level->value) {
                    if ($rule->level()->value > $level->value) {
                        $triggers = [];
                    }
                    $level = $rule->level();
                    $triggers[] = $allergy;
                }
            }
        }

        return new AlertResult($level, array_values(array_unique($triggers, SORT_REGULAR)));
    }
}

// ---------------------------------------------------------------------------
// EXTENSIÓN: requisito nuevo de quirófano. Se agrega una clase y se registra;
// AllergyAlertEvaluator, SevereAllergyRule y NonSevereAllergyRule no cambian.
// ---------------------------------------------------------------------------
final class LatexAllergyRule implements AllergyAlertRule
{
    public function appliesTo(Allergy $allergy): bool
    {
        return str_contains(mb_strtolower($allergy->allergen), 'látex')
            || str_contains(mb_strtolower($allergy->allergen), 'latex');
    }

    public function level(): AlertLevel
    {
        return AlertLevel::Red;
    }
}

// ---------------------------------------------------------------------------
// Aserciones
// ---------------------------------------------------------------------------
$failures = 0;
function check(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $ok ? 0 : 1;
}

$antes = new AllergyAlertServiceAntes();
$base = new AllergyAlertEvaluator([new SevereAllergyRule(), new NonSevereAllergyRule()]);

$casos = [
    'sin alergias → neutro' => [[], AlertLevel::Neutral],
    'penicilina leve → ámbar' => [[new Allergy('Penicilina', 'allergy', 'mild')], AlertLevel::Amber],
    'maní que amenaza la vida + polen leve → rojo' => [[
        new Allergy('Polen', 'allergy', 'mild'),
        new Allergy('Maní', 'allergy', 'life_threatening'),
    ], AlertLevel::Red],
    'lactosa (intolerancia, severidad desconocida) → ámbar' => [[new Allergy('Lactosa', 'intolerance', 'unknown')], AlertLevel::Amber],
    'alergia severa resuelta se ignora → neutro' => [[new Allergy('Sulfas', 'allergy', 'severe', 'resolved')], AlertLevel::Neutral],
    'consulta fallida → no disponible (falla segura)' => [null, AlertLevel::Unavailable],
];

echo "== Diseño después reproduce el comportamiento del diseño antes ==\n";
foreach ($casos as $label => [$allergies, $expected]) {
    $despues = $base->evaluate($allergies)->level;
    check($label, $despues === $expected && $antes->nivel($allergies) === $expected);
}

echo "\n== Extensión OCP: LatexAllergyRule ==\n";
$latex = [new Allergy('Látex', 'allergy', 'mild')];
check('sin la regla nueva, látex leve → ámbar', $base->evaluate($latex)->level === AlertLevel::Amber);

$extendido = new AllergyAlertEvaluator([new SevereAllergyRule(), new NonSevereAllergyRule(), new LatexAllergyRule()]);
$resultado = $extendido->evaluate($latex);
check('con la regla registrada, látex leve → rojo', $resultado->level === AlertLevel::Red);
check('la alergia que dispara el rojo es el látex', $resultado->triggers[0]->allergen === 'Látex');
check('las reglas anteriores siguen igual (penicilina leve → ámbar)', $extendido->evaluate([new Allergy('Penicilina', 'allergy', 'mild')])->level === AlertLevel::Amber);

$fuenteEvaluador = (new ReflectionClass(AllergyAlertEvaluator::class))->getStartLine();
$lineas = array_slice(file(__FILE__), $fuenteEvaluador - 1, (new ReflectionClass(AllergyAlertEvaluator::class))->getEndLine() - $fuenteEvaluador + 1);
check('AllergyAlertEvaluator no menciona ninguna regla concreta', !preg_match('/Latex|Severe|NonSevere/', implode('', $lineas)));

echo "\n" . ($failures === 0 ? 'OK: todas las aserciones pasaron' : "ERROR: {$failures} aserciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
