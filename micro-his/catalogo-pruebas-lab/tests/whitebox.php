<?php

declare(strict_types=1);

use MicroHis\CatalogoLab\Domain\DomainRuleViolation;
use MicroHis\CatalogoLab\Domain\LabTest;

require __DIR__ . '/../bootstrap.php';

/**
 * Caja blanca: un caso por rama de LabTest::register y assertRules (B1–B9), más los límites internos
 * de cada comparación (< frente a ≤). La tabla de ramas está en docs/catalogo-pruebas-lab/semana-15/README.md.
 */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
function rule(array $change): ?string
{
    $base = ['code' => 'glu', 'name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 1, 'result_type' => 'numeric', 'unit' => 'mg/dL',
        'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400, 'valid_from' => '2026-10-18'];
    try {
        LabTest::register('LT-1', array_merge($base, $change), 1, '2026-10-18');
        return null;
    } catch (DomainRuleViolation $v) {
        return "{$v->rule}:{$v->field}";
    }
}
check('B1 falta un obligatorio (name vacío) → FIELD_REQUIRED:name', rule(['name' => '  ']) === 'FIELD_REQUIRED:name');
check('B1 obligatorio que no es texto (code numérico) → FIELD_REQUIRED:code', rule(['code' => 123]) === 'FIELD_REQUIRED:code');
check('B2 volumen 0 → SPECIMEN_REQUIREMENT_INVALID', rule(['min_volume_ml' => 0]) === 'SPECIMEN_REQUIREMENT_INVALID:min_volume_ml');
check('B2 volumen no numérico se toma como 0 → SPECIMEN_REQUIREMENT_INVALID', rule(['min_volume_ml' => 'mucho']) === 'SPECIMEN_REQUIREMENT_INVALID:min_volume_ml');
check('B3 tipo no soportado (ordinal) → RESULT_TYPE_UNSUPPORTED', rule(['result_type' => 'ordinal']) === 'RESULT_TYPE_UNSUPPORTED:result_type');
check('B4 numérica sin unidad → RANGES_INCOHERENT:unit', rule(['unit' => null]) === 'RANGES_INCOHERENT:unit');
check('B4 numérica con un rango faltante → RANGES_INCOHERENT:unit', rule(['critical_high' => null]) === 'RANGES_INCOHERENT:unit');
check('B5 crítico bajo = referencia baja (límite de <) → RANGES_INCOHERENT', rule(['critical_low' => 70]) === 'RANGES_INCOHERENT:reference_low');
check('B5 referencia baja = referencia alta (límite de ≤) → válida', rule(['reference_low' => 100]) === null);
check('B5 referencia alta = crítico alto (límite de <) → RANGES_INCOHERENT', rule(['critical_high' => 100]) === 'RANGES_INCOHERENT:reference_low');
check('B6 cualitativa con un solo rango → RESULT_DEFINITION_INVALID', rule(['result_type' => 'qualitative', 'unit' => null, 'reference_low' => null, 'reference_high' => null, 'critical_high' => null]) === 'RESULT_DEFINITION_INVALID:result_type');
check('B7 cualitativa sin rangos → válida', rule(['result_type' => 'qualitative', 'unit' => null, 'reference_low' => null, 'reference_high' => null, 'critical_low' => null, 'critical_high' => null]) === null);
check('B8 vigencia con formato inválido → VALIDITY_INVALID', rule(['valid_from' => '18/10/2026']) === 'VALIDITY_INVALID:valid_from');
check('B8 vigencia de ayer → VALIDITY_INVALID', rule(['valid_from' => '2026-10-17']) === 'VALIDITY_INVALID:valid_from');
check('B9 vigencia de hoy (límite) → válida', rule([]) === null);
$t = LabTest::register('LT-2', ['code' => ' na ', 'name' => ' Sodio ', 'specimen_type' => 'serum', 'min_volume_ml' => '0.5', 'result_type' => 'text', 'valid_from' => '2026-12-01'], 3, '2026-10-18');
check('B9 normaliza código y nombre, convierte números y conserva la versión', $t->code === 'NA' && $t->name === 'Sodio' && $t->minVolumeMl === 0.5 && $t->versionNo === 3);
check('B10 nombre de 161 caracteres → TEXT_TOO_LONG; 160 con acentos es válido', rule(['name' => str_repeat('n', 161)]) === 'TEXT_TOO_LONG:name' && rule(['name' => str_repeat('é', 160)]) === null);
check('B10 unidad de 33 caracteres → TEXT_TOO_LONG', rule(['unit' => str_repeat('u', 33)]) === 'TEXT_TOO_LONG:unit');
check('B11 unidad numérica o arreglo → ausente (RANGES_INCOHERENT), nunca TypeError', rule(['unit' => 5]) === 'RANGES_INCOHERENT:unit' && rule(['unit' => ['mg']]) === 'RANGES_INCOHERENT:unit');
check('B11 rango infinito (1e400) → ausente (RANGES_INCOHERENT)', rule(['critical_high' => '1e400']) === 'RANGES_INCOHERENT:unit');
echo 'Todas las pruebas de caja blanca pasaron.' . PHP_EOL;
