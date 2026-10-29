<?php

declare(strict_types=1);

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Domain\Allergy;
use MicroHis\Alergias\Domain\AllergyAlert;
use MicroHis\Alergias\Domain\DomainRuleViolation;
use MicroHis\Alergias\Persistence\InMemoryAllergyRepository;

require __DIR__ . '/../bootstrap.php';

/**
 * Caja blanca: un caso por rama de Allergy::register (B1–B5), de AllergyAlert::from (B6–B9) y de la falla segura
 * de GetAllergyAlert (B10). La tabla de ramas está en docs/alergias/semana-15/README.md.
 */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
function rule(array $change): ?string
{
    try {
        Allergy::register('A-1', 'P-1', array_merge(['allergen_code' => 'RXN-1', 'allergen_name' => 'Penicilina', 'allergy_type' => 'allergy', 'severity' => 'mild'], $change));
        return null;
    } catch (DomainRuleViolation $v) {
        return "{$v->rule}:{$v->field}";
    }
}
$level = fn (string ...$severities) => AllergyAlert::from(array_map(fn ($s) => ['severity' => $s], $severities))->level;
check('B1 sin código ni nombre (solo espacios) → ALLERGEN_REQUIRED', rule(['allergen_code' => ' ', 'allergen_name' => '']) === 'ALLERGEN_REQUIRED:allergen_name');
check('B2 solo código → válida', rule(['allergen_name' => null]) === null);
check('B2 solo nombre → válida', rule(['allergen_code' => null]) === null);
check('B3 tipo fuera de la lista → ALLERGY_TYPE_INVALID', rule(['allergy_type' => 'alergia']) === 'ALLERGY_TYPE_INVALID:allergy_type');
check('B4 severidad fuera de la lista → SEVERITY_INVALID', rule(['severity' => 'grave']) === 'SEVERITY_INVALID:severity');
check('B11 nombre de 201 caracteres → TEXT_TOO_LONG', rule(['allergen_name' => str_repeat('á', 201)]) === 'TEXT_TOO_LONG:allergen_name');
check('B11 reacción de 501 caracteres → TEXT_TOO_LONG', rule(['reaction' => str_repeat('x', 501)]) === 'TEXT_TOO_LONG:reaction');
check('B11 justo en el límite (200 y 50) → válida', rule(['allergen_name' => str_repeat('á', 200), 'allergen_code' => str_repeat('C', 50)]) === null);
$a = Allergy::register('A-2', 'P-1', ['allergen_code' => ' RXN-2 ', 'allergen_name' => '  ', 'reaction' => ' ', 'allergy_type' => 'intolerance', 'severity' => 'unknown']);
check('B5 válida: recorta textos y convierte los vacíos en null', $a->allergenCode === 'RXN-2' && $a->allergenName === null && $a->reaction === null);
check('B6 sin alergias activas → none', $level() === 'none');
check('B7 una severe → critical', $level('severe') === 'critical');
check('B7 una life_threatening entre leves → critical', $level('mild', 'life_threatening') === 'critical');
check('B8 solo mild, moderate o unknown → warning', $level('mild', 'moderate', 'unknown') === 'warning');
check('B9 unavailable() nunca trae alergias', AllergyAlert::unavailable()->level === 'unavailable' && AllergyAlert::unavailable()->allergies === []);
$repo = new InMemoryAllergyRepository(['P-1']);
$repo->failReads = true;
check('B10 la lectura falla → GetAllergyAlert devuelve unavailable (falla segura)', (new GetAllergyAlert($repo))->execute('P-1')->level === 'unavailable');
echo 'Todas las pruebas de caja blanca pasaron.' . PHP_EOL;
