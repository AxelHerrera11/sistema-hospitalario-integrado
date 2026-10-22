<?php

declare(strict_types=1);

/*
 * ASII-17 · Semana 2 · Evidencia verificable de OCP.
 * Ejecutar: php docs/catalogo-pruebas-lab/semana-02/evidencia/demo-ocp.php
 * Compara el "antes" (switch por tipo de resultado) con el "después" (ResultTypeSpec por tipo +
 * reglas LabTestRule) y demuestra que OrdinalResultSpec se agrega sin modificar el validador.
 */

final readonly class LabTestDraft
{
    /** @param list<string> $allowedValues */
    public function __construct(
        public string $resultType, public string $container = 'tubo rojo', public float $minVolumeMl = 1.0,
        public bool $fastingRequired = false, public ?int $fastingHours = null, public ?string $unit = null,
        public ?float $referenceLow = null, public ?float $referenceHigh = null, public ?float $criticalLow = null,
        public array $allowedValues = [], public ?string $criticalFrom = null, public string $validFrom = '2026-11-01',
    ) {
    }

    public function rangesOrdered(): bool
    {
        return ($this->referenceLow === null || $this->referenceHigh === null || $this->referenceLow <= $this->referenceHigh)
            && ($this->criticalLow === null || $this->referenceLow === null || $this->criticalLow < $this->referenceLow);
    }

    public function hasNumericRanges(): bool { return $this->referenceLow !== null || $this->referenceHigh !== null || $this->criticalLow !== null; }
    public function specimenInvalid(): bool { return $this->minVolumeMl <= 0 || trim($this->container) === '' || ($this->fastingRequired && ($this->fastingHours ?? 0) <= 0); }
}

// ANTES: un tipo nuevo (ordinal 1+…4+, título, cultivo) obliga a editar el switch.
final class LabTestCatalogServiceAntes
{
    /** @return list<string> */
    public function validar(LabTestDraft $d, string $today): array
    {
        $errors = $d->specimenInvalid() ? ['SPECIMEN_REQUIREMENT_INVALID'] : [];
        switch ($d->resultType) {
            case 'numeric': $bad = $d->unit === null || !$d->rangesOrdered(); $errors = [...$errors, ...($bad ? ['RANGES_INCOHERENT'] : [])]; break;
            case 'qualitative': $bad = $d->hasNumericRanges() || count($d->allowedValues) < 2; $errors = [...$errors, ...($bad ? ['RESULT_DEFINITION_INVALID'] : [])]; break;
            default: $errors[] = 'RESULT_TYPE_UNSUPPORTED'; // ← aquí habría que editar
        }
        $errors = [...$errors, ...($d->validFrom < $today ? ['VALIDITY_INVALID'] : [])];
        sort($errors);
        return $errors;
    }
}

// DESPUÉS: abierto a extensión (tipos y reglas nuevas), cerrado a modificación.
interface ResultTypeSpec
{
    public function type(): string;
    /** @return list<string> */
    public function validate(LabTestDraft $draft): array;
}

interface LabTestRule
{
    /** @return list<string> */
    public function validate(LabTestDraft $draft, string $today): array;
}

final class NumericResultSpec implements ResultTypeSpec
{
    public function type(): string { return 'numeric'; }
    public function validate(LabTestDraft $d): array { return $d->unit === null || !$d->rangesOrdered() ? ['RANGES_INCOHERENT'] : []; }
}

final class QualitativeResultSpec implements ResultTypeSpec
{
    public function type(): string { return 'qualitative'; }
    public function validate(LabTestDraft $d): array { return $d->hasNumericRanges() || count($d->allowedValues) < 2 ? ['RESULT_DEFINITION_INVALID'] : []; }
}

final class SpecimenRequirementRule implements LabTestRule
{
    public function validate(LabTestDraft $d, string $today): array { return $d->specimenInvalid() ? ['SPECIMEN_REQUIREMENT_INVALID'] : []; }
}

final class ValidityRule implements LabTestRule
{
    public function validate(LabTestDraft $d, string $today): array { return $d->validFrom < $today ? ['VALIDITY_INVALID'] : []; }
}

final class ResultTypeRegistry
{
    /** @var array<string, ResultTypeSpec> */
    private array $specs = [];

    /** @param iterable<ResultTypeSpec> $specs */
    public function __construct(iterable $specs)
    {
        foreach ($specs as $spec) {
            $this->specs[$spec->type()] = $spec;
        }
    }

    public function for(string $type): ?ResultTypeSpec { return $this->specs[$type] ?? null; }
}

final class LabTestValidator
{
    /** @param iterable<LabTestRule> $rules */
    public function __construct(private readonly ResultTypeRegistry $types, private readonly iterable $rules) {}

    /** @return list<string> errores ordenados */
    public function validate(LabTestDraft $draft, string $today): array
    {
        $errors = [];
        foreach ($this->rules as $rule) {
            $errors = [...$errors, ...$rule->validate($draft, $today)];
        }
        $spec = $this->types->for($draft->resultType);
        $errors = [...$errors, ...($spec === null ? ['RESULT_TYPE_UNSUPPORTED'] : $spec->validate($draft))];
        sort($errors);
        return $errors;
    }
}

// EXTENSIÓN: resultado ordinal (proteinuria negativo…4+). Clase nueva + registro, nada más.
final class OrdinalResultSpec implements ResultTypeSpec
{
    public function type(): string { return 'ordinal'; }
    public function validate(LabTestDraft $d): array
    {
        $ok = !$d->hasNumericRanges() && count($d->allowedValues) >= 2 && count(array_unique($d->allowedValues)) === count($d->allowedValues)
            && ($d->criticalFrom === null || in_array($d->criticalFrom, $d->allowedValues, true));
        return $ok ? [] : ['RESULT_DEFINITION_INVALID'];
    }
}

$failures = 0;
function check(string $label, bool $ok): void { global $failures; echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n"; $failures += $ok ? 0 : 1; }

$today = '2026-10-12';
$specs = [new NumericResultSpec(), new QualitativeResultSpec()];
$rules = [new SpecimenRequirementRule(), new ValidityRule()];
$base = new LabTestValidator(new ResultTypeRegistry($specs), $rules);
$glucosa = new LabTestDraft('numeric', unit: 'mg/dL', referenceLow: 70, referenceHigh: 100, criticalLow: 40);

echo "== Diseño después reproduce el comportamiento del diseño antes ==\n";
foreach ([
    'glucosa numérica válida → sin errores' => [$glucosa, []],
    'crítico bajo ≥ referencia baja → RANGES_INCOHERENT' => [new LabTestDraft('numeric', unit: 'mmol/L', referenceLow: 135, referenceHigh: 145, criticalLow: 140), ['RANGES_INCOHERENT']],
    'ayuno sin horas → SPECIMEN_REQUIREMENT_INVALID' => [new LabTestDraft('numeric', fastingRequired: true, unit: 'mg/dL'), ['SPECIMEN_REQUIREMENT_INVALID']],
    'alta retroactiva → VALIDITY_INVALID' => [new LabTestDraft('qualitative', allowedValues: ['reactivo', 'no reactivo'], validFrom: '2026-01-01'), ['VALIDITY_INVALID']],
    'cualitativa sin valores permitidos → RESULT_DEFINITION_INVALID' => [new LabTestDraft('qualitative'), ['RESULT_DEFINITION_INVALID']],
    'tipo ordinal sin soporte → RESULT_TYPE_UNSUPPORTED' => [new LabTestDraft('ordinal', allowedValues: ['negativo', '1+']), ['RESULT_TYPE_UNSUPPORTED']],
] as $label => [$draft, $expected]) {
    check($label, $base->validate($draft, $today) === $expected && (new LabTestCatalogServiceAntes())->validar($draft, $today) === $expected);
}

echo "\n== Extensión OCP: OrdinalResultSpec ==\n";
$extendido = new LabTestValidator(new ResultTypeRegistry([...$specs, new OrdinalResultSpec()]), $rules);
$escala = ['negativo', '1+', '2+', '3+', '4+'];
check('con la especificación registrada, la proteinuria ordinal es válida', $extendido->validate(new LabTestDraft('ordinal', container: 'frasco estéril', allowedValues: $escala, criticalFrom: '4+'), $today) === []);
check('umbral crítico fuera de la escala → RESULT_DEFINITION_INVALID', $extendido->validate(new LabTestDraft('ordinal', allowedValues: $escala, criticalFrom: '5+'), $today) === ['RESULT_DEFINITION_INVALID']);
check('las reglas comunes también aplican al tipo nuevo (alta retroactiva)', $extendido->validate(new LabTestDraft('ordinal', allowedValues: $escala, validFrom: '2026-01-01'), $today) === ['VALIDITY_INVALID']);
check('los tipos anteriores siguen igual (glucosa válida)', $extendido->validate($glucosa, $today) === []);
foreach ([LabTestValidator::class, ResultTypeRegistry::class] as $class) {
    $ref = new ReflectionClass($class);
    $codigo = implode('', array_slice(file(__FILE__), $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    check("{$class} no menciona ningún tipo de resultado concreto", !preg_match('/Numeric|Qualitative|Ordinal|\'numeric\'|\'ordinal\'/', $codigo));
}

echo "\n" . ($failures === 0 ? 'OK: todas las aserciones pasaron' : "ERROR: {$failures} aserciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
