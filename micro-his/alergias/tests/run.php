<?php

declare(strict_types=1);

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Persistence\PdoAllergyStore;
use MicroHis\Alergias\Presentation\AllergyHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** Base de prueba: SQLite en memoria con el esquema del módulo y datos ficticios. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO local_patients VALUES ('P-1'), ('P-2')");
    $store = new PdoAllergyStore($pdo);
    return [$pdo, new AllergyHttpHandler(new RegisterAllergy($store, fn () => uniqid('ALG-')), new GetAllergyAlert($store))];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$penicilina = ['allergen_code' => 'RXN-7980', 'allergen_name' => 'Penicilina', 'reaction' => 'Anafilaxia', 'allergy_type' => 'allergy', 'severity' => 'life_threatening'];
[$pdo, $http] = app();
check('paciente sin alergias → nivel none', $http->handle('GET', '/patients/P-1/allergy-alert', [], 'medico1')[1]['alert']['level'] === 'none');
[$status, $body] = $http->handle('POST', '/patients/P-1/allergies', $penicilina, 'medico1');
check('alta válida → 201', $status === 201 && $body['allergy']['severity'] === 'life_threatening');
check('el alta queda auditada en la misma transacción', $pdo->query('SELECT event FROM local_access_audits')->fetchColumn() === 'alergia.registrada');
[$status, $body] = $http->handle('GET', '/patients/P-1/allergy-alert', [], 'medico1');
check('alergia que amenaza la vida → banner critical', $status === 200 && $body['alert']['level'] === 'critical' && count($body['alert']['allergies']) === 1);
check('misma alergia activa otra vez (otro texto y mayúsculas) → 409', $http->handle('POST', '/patients/P-1/allergies', ['allergen_code' => ' rxn-7980 ', 'severity' => 'mild'] + $penicilina, 'medico1')[0] === 409);
$http->handle('POST', '/patients/P-2/allergies', ['allergen_code' => null, 'allergen_name' => 'Lactosa', 'allergy_type' => 'intolerance', 'severity' => 'mild'], 'medico1');
check('intolerancia leve → banner warning', $http->handle('GET', '/patients/P-2/allergy-alert', [], 'medico1')[1]['alert']['level'] === 'warning');
foreach ([
    'sin alérgeno → 422 ALLERGEN_REQUIRED' => [['allergen_code' => '', 'allergen_name' => ' '], 'ALLERGEN_REQUIRED'],
    'tipo inválido → 422 ALLERGY_TYPE_INVALID' => [['allergy_type' => 'alergia'], 'ALLERGY_TYPE_INVALID'],
    'severidad inválida → 422 SEVERITY_INVALID' => [['severity' => 'grave'], 'SEVERITY_INVALID'],
] as $name => [$change, $rule]) {
    check($name, $http->handle('POST', '/patients/P-2/allergies', array_merge($penicilina, $change), 'medico1')[1]['error'] === $rule);
}
check('paciente inexistente → 404 PATIENT_NOT_FOUND', $http->handle('POST', '/patients/P-9/allergies', $penicilina, 'medico1')[0] === 404);
check('sin usuario → 401', $http->handle('GET', '/patients/P-1/allergy-alert', [], null)[0] === 401);
check('ningún alta rechazada se guardó', (int) $pdo->query('SELECT COUNT(*) FROM allergies')->fetchColumn() === 2);
[$pdo, $http] = app();
$pdo->exec('DROP TABLE local_access_audits');
check('falla de auditoría → 500 y rollback sin alergia guardada', $http->handle('POST', '/patients/P-1/allergies', $penicilina, 'medico1')[0] === 500
    && (int) $pdo->query('SELECT COUNT(*) FROM allergies')->fetchColumn() === 0);
$pdo->exec('DROP TABLE allergies');
check('base sin respuesta → «no disponible», nunca «sin alergias» (falla segura)', $http->handle('GET', '/patients/P-1/allergy-alert', [], 'medico1')[1]['alert']['level'] === 'unavailable');
echo 'Todas las pruebas pasaron.' . PHP_EOL;
