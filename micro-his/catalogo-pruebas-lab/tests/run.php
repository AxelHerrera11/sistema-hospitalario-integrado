<?php

declare(strict_types=1);

use MicroHis\CatalogoLab\Application\FindActiveLabTests;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Persistence\PdoLabTestStore;
use MicroHis\CatalogoLab\Presentation\LabTestHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** Base de prueba: SQLite en memoria con el esquema del módulo y datos ficticios. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $store = new PdoLabTestStore($pdo);
    $register = new RegisterLabTest($store, fn () => '2026-10-18', fn () => uniqid('LT-'));
    return [$pdo, new LabTestHttpHandler($register, new FindActiveLabTests($store))];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$glucosa = ['code' => 'glu', 'name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 1, 'result_type' => 'numeric',
    'unit' => 'mg/dL', 'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400, 'valid_from' => '2026-10-19'];
[$pdo, $http] = app();
[$status, $body] = $http->handle('POST', '/lab-tests', $glucosa, 'jefa.lab');
check('alta válida → 201 con versión 1 y código normalizado', $status === 201 && $body['lab_test']['versionNo'] === 1 && $body['lab_test']['code'] === 'GLU');
check('el alta queda auditada en la misma transacción', $pdo->query("SELECT event FROM local_access_audits")->fetchColumn() === 'prueba_lab.registrada');
check('sin usuario → 401', $http->handle('POST', '/lab-tests', $glucosa, null)[0] === 401);
check('segunda versión con vigencia abierta → 409 LAB_TEST_VALIDITY_OVERLAP', $http->handle('POST', '/lab-tests', $glucosa, 'jefa.lab') === [409, ['error' => 'LAB_TEST_VALIDITY_OVERLAP', 'field' => 'valid_from']]);
foreach ([
    'crítico bajo ≥ referencia baja → 422 RANGES_INCOHERENT' => [['code' => 'NA', 'critical_low' => 80], 'RANGES_INCOHERENT'],
    'volumen 0 → 422 SPECIMEN_REQUIREMENT_INVALID' => [['code' => 'TG', 'min_volume_ml' => 0], 'SPECIMEN_REQUIREMENT_INVALID'],
    'alta retroactiva → 422 VALIDITY_INVALID' => [['code' => 'CL', 'valid_from' => '2026-01-01'], 'VALIDITY_INVALID'],
    'cualitativa con rangos numéricos → 422 RESULT_DEFINITION_INVALID' => [['code' => 'HIV', 'result_type' => 'qualitative'], 'RESULT_DEFINITION_INVALID'],
    'falta el nombre → 422 FIELD_REQUIRED' => [['code' => 'HB', 'name' => ' '], 'FIELD_REQUIRED'],
] as $name => [$change, $rule]) {
    [$status, $body] = $http->handle('POST', '/lab-tests', array_merge($glucosa, $change), 'jefa.lab');
    check($name, $status === 422 && $body['error'] === $rule);
}
check('ningún alta rechazada se guardó', (int) $pdo->query('SELECT COUNT(*) FROM lab_tests')->fetchColumn() === 1);
check('consulta antes de la vigencia → lista vacía', $http->handle('GET', '/lab-tests', ['date' => '2026-10-18'], 'medico1') === [200, ['lab_tests' => []]]);
[$status, $body] = $http->handle('GET', '/lab-tests', ['date' => '2026-10-20', 'search' => 'gluc'], 'medico1');
check('búsqueda «gluc» en fecha vigente → GLU versión 1', $status === 200 && array_column($body['lab_tests'], 'code') === ['GLU']);
[$pdo, $http] = app();
$pdo->exec('DROP TABLE local_access_audits');
check('falla de auditoría → 500 PERSISTENCE_FAILURE', $http->handle('POST', '/lab-tests', $glucosa, 'jefa.lab')[0] === 500);
check('rollback: la prueba no quedó guardada sin su auditoría', (int) $pdo->query('SELECT COUNT(*) FROM lab_tests')->fetchColumn() === 0);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
