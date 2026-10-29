<?php

declare(strict_types=1);

use MicroHis\CatalogoLab\Application\FindActiveLabTests;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Persistence\InMemoryLabTestRepository;
use MicroHis\CatalogoLab\Presentation\ApiKernel;
use MicroHis\CatalogoLab\Presentation\LabTestController;
use MicroHis\CatalogoLab\Presentation\LabTestListView;

require __DIR__ . '/../bootstrap.php';

/**
 * Caja negra: solo el contrato HTTP de openapi.yaml (sin mirar el código), con particiones de equivalencia
 * y valores límite. Cada caso usa un servidor nuevo (adaptador InMemory) para que sea independiente.
 */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
function post(array $change, ?InMemoryLabTestRepository $repo = null): array
{
    $repo ??= new InMemoryLabTestRepository();
    $kernel = new ApiKernel(new LabTestController(new RegisterLabTest($repo, fn () => '2026-10-18', fn () => uniqid('LT-')), new FindActiveLabTests($repo), new LabTestListView()), 'HOSP-DEMO', fn () => true);
    $body = array_merge(['code' => 'GLU', 'name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 1, 'result_type' => 'numeric', 'unit' => 'mg/dL',
        'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400, 'valid_from' => '2026-10-19'], $change);
    [$status, , $out] = $kernel->handle('POST', '/api/v1/lab-tests', ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'jefa.lab'], json_encode($body));
    return [$status, json_decode($out, true)['code'] ?? null];
}
// Partición: result_type (3 válidos y 1 inválido).
check('result_type numeric (válido) → 201', post([])[0] === 201);
check('result_type text sin rangos (válido) → 201', post(['result_type' => 'text', 'unit' => null, 'reference_low' => null, 'reference_high' => null, 'critical_low' => null, 'critical_high' => null])[0] === 201);
check('result_type fuera del enum → 422 RESULT_TYPE_UNSUPPORTED', post(['result_type' => 'titer']) === [422, 'RESULT_TYPE_UNSUPPORTED']);
// Valores límite de min_volume_ml (exclusiveMinimum: 0).
check('min_volume_ml = 0 (límite excluido) → 422', post(['min_volume_ml' => 0]) === [422, 'SPECIMEN_REQUIREMENT_INVALID']);
check('min_volume_ml = 0.01 (justo encima) → 201', post(['min_volume_ml' => 0.01])[0] === 201);
check('min_volume_ml = -1 (partición negativa) → 422', post(['min_volume_ml' => -1])[0] === 422);
// Valores límite de los rangos alrededor de la referencia baja (70).
check('critical_low = 69.99 (justo debajo de 70) → 201', post(['critical_low' => 69.99])[0] === 201);
check('critical_low = 70 (igual a la referencia) → 422 RANGES_INCOHERENT', post(['critical_low' => 70]) === [422, 'RANGES_INCOHERENT']);
// Valores límite de valid_from (no retroactiva; hoy = 2026-10-18).
check('valid_from = hoy → 201', post(['valid_from' => '2026-10-18'])[0] === 201);
check('valid_from = ayer → 422 VALIDITY_INVALID', post(['valid_from' => '2026-10-17']) === [422, 'VALIDITY_INVALID']);
// Partición: código ya vigente (409) frente a código nuevo.
$repo = new InMemoryLabTestRepository();
post([], $repo);
check('mismo código con vigencia abierta → 409 LAB_TEST_VALIDITY_OVERLAP', post([], $repo) === [409, 'LAB_TEST_VALIDITY_OVERLAP']);
check('otro código en el mismo catálogo → 201', post(['code' => 'NA'], $repo)[0] === 201);
// Partición: cuerpo y encabezados inválidos.
check('campo obligatorio ausente (name) → 422 FIELD_REQUIRED', post(['name' => null]) === [422, 'FIELD_REQUIRED']);
echo 'Todas las pruebas de caja negra pasaron.' . PHP_EOL;
