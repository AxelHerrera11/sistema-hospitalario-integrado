<?php

declare(strict_types=1);

/*
 * ASII-07 · Semana 2 · Evidencia verificable de OCP.
 *
 * Ejecutar: php docs/admision-camas/semana-02/evidencia/demo-ocp.php
 *
 * Compara el diseño "antes" (cadena de if en el servicio) con el diseño
 * "después" (reglas que implementan BedEligibilityRule) y demuestra que una
 * regla nueva (PediatricWardAgeRule) cambia qué camas se pueden asignar sin
 * modificar BedEligibilityChecker. PHP 8.2+, sin framework ni base de datos.
 */

final readonly class Ward
{
    public function __construct(public string $code, public string $status, public string $kind = 'general')
    {
    }
}

final readonly class Bed
{
    public function __construct(public string $code, public Ward $ward, public string $type, public string $status)
    {
    }
}

final readonly class AdmissionContext
{
    public function __construct(
        public string $episodeType,          // care_episodes.type
        public string $episodeStatus,        // care_episodes.status
        public bool $hasOpenBedStay,         // ¿bed_stays abierta?
        public int $patientAgeYears,
        public ?string $requestedBedType = null,
    ) {
    }
}

final readonly class RuleResult
{
    private function __construct(public bool $ok, public ?string $code)
    {
    }

    public static function pass(): self
    {
        return new self(true, null);
    }

    public static function fail(string $code): self
    {
        return new self(false, $code);
    }
}

// ---------------------------------------------------------------------------
// ANTES: el servicio decide la elegibilidad con una cadena de if. Cada regla
// nueva (pediatría, aislamiento, sala por sexo…) obliga a modificarlo.
// ---------------------------------------------------------------------------
final class AdmissionServiceAntes
{
    public function motivoRechazo(Bed $bed, AdmissionContext $ctx): ?string
    {
        if ($ctx->episodeType !== 'inpatient' || $ctx->episodeStatus !== 'open') {
            return 'EPISODE_NOT_OPEN';
        }
        if ($ctx->hasOpenBedStay) {
            return 'EPISODE_ALREADY_HAS_BED';
        }
        if ($bed->status !== 'available' || $bed->ward->status !== 'active') {
            return 'BED_NOT_AVAILABLE';
        }
        if ($ctx->requestedBedType !== null && $ctx->requestedBedType !== $bed->type) {
            return 'BED_TYPE_MISMATCH';
        }
        // if ($bed->ward->kind === 'pediatric' && ...)  ← aquí habría que editar

        return null;
    }
}

// ---------------------------------------------------------------------------
// DESPUÉS: abierto a extensión (reglas nuevas), cerrado a modificación.
// ---------------------------------------------------------------------------
interface BedEligibilityRule
{
    public function evaluate(Bed $bed, AdmissionContext $ctx): RuleResult;
}

final class EpisodeOpenInpatientRule implements BedEligibilityRule
{
    public function evaluate(Bed $bed, AdmissionContext $ctx): RuleResult
    {
        return $ctx->episodeType === 'inpatient' && $ctx->episodeStatus === 'open'
            ? RuleResult::pass() : RuleResult::fail('EPISODE_NOT_OPEN');
    }
}

final class EpisodeHasNoOpenBedRule implements BedEligibilityRule
{
    public function evaluate(Bed $bed, AdmissionContext $ctx): RuleResult
    {
        return $ctx->hasOpenBedStay ? RuleResult::fail('EPISODE_ALREADY_HAS_BED') : RuleResult::pass();
    }
}

final class BedAvailableRule implements BedEligibilityRule
{
    public function evaluate(Bed $bed, AdmissionContext $ctx): RuleResult
    {
        return $bed->status === 'available' && $bed->ward->status === 'active'
            ? RuleResult::pass() : RuleResult::fail('BED_NOT_AVAILABLE');
    }
}

final class BedTypeMatchesRule implements BedEligibilityRule
{
    public function evaluate(Bed $bed, AdmissionContext $ctx): RuleResult
    {
        return $ctx->requestedBedType === null || $ctx->requestedBedType === $bed->type
            ? RuleResult::pass() : RuleResult::fail('BED_TYPE_MISMATCH');
    }
}

final class BedEligibilityChecker
{
    /** @param iterable<BedEligibilityRule> $rules en orden de evaluación */
    public function __construct(private readonly iterable $rules)
    {
    }

    /** Primer motivo de rechazo o null si la cama es elegible (UC-ADM-04/05). */
    public function rejection(Bed $bed, AdmissionContext $ctx): ?string
    {
        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($bed, $ctx);
            if (!$result->ok) {
                return $result->code;
            }
        }

        return null;
    }

    /**
     * Camas elegibles para la búsqueda (UC-ADM-03).
     *
     * @param list<Bed> $beds
     * @return list<string>
     */
    public function eligibleCodes(array $beds, AdmissionContext $ctx): array
    {
        $eligible = array_filter($beds, fn (Bed $bed): bool => $this->rejection($bed, $ctx) === null);

        return array_values(array_map(fn (Bed $bed): string => $bed->code, $eligible));
    }
}

// ---------------------------------------------------------------------------
// EXTENSIÓN: requisito nuevo de pediatría. Se agrega una clase y se registra;
// BedEligibilityChecker y las reglas existentes no cambian.
// ---------------------------------------------------------------------------
final class PediatricWardAgeRule implements BedEligibilityRule
{
    public function __construct(private readonly int $maxAgeYears = 14)
    {
    }

    public function evaluate(Bed $bed, AdmissionContext $ctx): RuleResult
    {
        return $bed->ward->kind !== 'pediatric' || $ctx->patientAgeYears <= $this->maxAgeYears
            ? RuleResult::pass() : RuleResult::fail('PEDIATRIC_WARD_AGE');
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

$medicina = new Ward('MED-A', 'active');
$pediatria = new Ward('PED-1', 'active', 'pediatric');
$cerrada = new Ward('MED-B', 'inactive');
$camas = [
    new Bed('MED-A-01', $medicina, 'standard', 'available'),
    new Bed('MED-A-02', $medicina, 'standard', 'occupied'),
    new Bed('MED-A-03', $medicina, 'isolation', 'available'),
    new Bed('MED-B-01', $cerrada, 'standard', 'available'),
    new Bed('PED-1-01', $pediatria, 'standard', 'available'),
];
$adulto = new AdmissionContext('inpatient', 'open', false, 40);

$antes = new AdmissionServiceAntes();
$base = new BedEligibilityChecker([
    new EpisodeOpenInpatientRule(), new EpisodeHasNoOpenBedRule(), new BedAvailableRule(), new BedTypeMatchesRule(),
]);

$casos = [
    'cama libre en sala activa → elegible' => [$camas[0], $adulto, null],
    'cama ocupada → BED_NOT_AVAILABLE' => [$camas[1], $adulto, 'BED_NOT_AVAILABLE'],
    'sala inactiva → BED_NOT_AVAILABLE' => [$camas[3], $adulto, 'BED_NOT_AVAILABLE'],
    'tipo distinto al solicitado → BED_TYPE_MISMATCH' => [$camas[0], new AdmissionContext('inpatient', 'open', false, 40, 'isolation'), 'BED_TYPE_MISMATCH'],
    'episodio con cama abierta → EPISODE_ALREADY_HAS_BED' => [$camas[0], new AdmissionContext('inpatient', 'open', true, 40), 'EPISODE_ALREADY_HAS_BED'],
    'episodio cerrado → EPISODE_NOT_OPEN' => [$camas[0], new AdmissionContext('inpatient', 'closed', false, 40), 'EPISODE_NOT_OPEN'],
];

echo "== Diseño después reproduce el comportamiento del diseño antes ==\n";
foreach ($casos as $label => [$bed, $ctx, $expected]) {
    check($label, $base->rejection($bed, $ctx) === $expected && $antes->motivoRechazo($bed, $ctx) === $expected);
}
check('la búsqueda (UC-ADM-03) usa las mismas reglas', $base->eligibleCodes($camas, $adulto) === ['MED-A-01', 'MED-A-03', 'PED-1-01']);

echo "\n== Extensión OCP: PediatricWardAgeRule ==\n";
check('sin la regla nueva, un adulto podría ocupar una cama pediátrica', $base->rejection($camas[4], $adulto) === null);

$extendido = new BedEligibilityChecker([
    new EpisodeOpenInpatientRule(), new EpisodeHasNoOpenBedRule(), new BedAvailableRule(), new BedTypeMatchesRule(),
    new PediatricWardAgeRule(),
]);
check('con la regla registrada, adulto en sala pediátrica → PEDIATRIC_WARD_AGE', $extendido->rejection($camas[4], $adulto) === 'PEDIATRIC_WARD_AGE');
check('un niño de 8 años sí puede ocupar la cama pediátrica', $extendido->rejection($camas[4], new AdmissionContext('inpatient', 'open', false, 8)) === null);
check('la búsqueda del adulto ya no ofrece la cama pediátrica', $extendido->eligibleCodes($camas, $adulto) === ['MED-A-01', 'MED-A-03']);
check('las reglas anteriores siguen igual (cama ocupada → BED_NOT_AVAILABLE)', $extendido->rejection($camas[1], $adulto) === 'BED_NOT_AVAILABLE');

$ref = new ReflectionClass(BedEligibilityChecker::class);
$codigo = implode('', array_slice(file(__FILE__), $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
check('BedEligibilityChecker no menciona ninguna regla concreta', !preg_match('/Pediatric|Available|TypeMatches|OpenInpatient|NoOpenBed/', $codigo));

echo "\n" . ($failures === 0 ? 'OK: todas las aserciones pasaron' : "ERROR: {$failures} aserciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
