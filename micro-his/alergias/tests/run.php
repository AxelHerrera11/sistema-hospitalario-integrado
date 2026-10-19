<?php

declare(strict_types=1);

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Persistence\InMemoryAllergyRepository;
use MicroHis\Alergias\Persistence\PdoAllergyRepository;
use MicroHis\Alergias\Presentation\AllergyBannerView;
use MicroHis\Alergias\Presentation\AllergyController;

require __DIR__ . '/../bootstrap.php';

/**
 * Prueba de contrato: la misma batería corre con los dos adaptadores del Repository.
 * $probe da lo que cada uno expone distinto: filas guardadas, último evento auditado y cómo simular las fallas.
 */
function app(string $adapter): array
{
    if ($adapter === 'InMemory') {
        $repo = new InMemoryAllergyRepository(['P-1', 'P-2']);
        $probe = ['count' => fn () => count($repo->rows), 'audit' => fn () => $repo->audit[0][0] ?? null,
            'failWrites' => fn () => $repo->failWrites = true, 'failReads' => fn () => $repo->failReads = true];
    } else {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
        $pdo->exec("INSERT INTO local_patients VALUES ('P-1'), ('P-2')");
        $repo = new PdoAllergyRepository($pdo);
        $probe = ['count' => fn () => (int) $pdo->query('SELECT COUNT(*) FROM allergies')->fetchColumn(),
            'audit' => fn () => $pdo->query('SELECT action FROM local_access_audits')->fetchColumn(),
            'failWrites' => fn () => $pdo->exec('DROP TABLE local_access_audits'), 'failReads' => fn () => $pdo->exec('DROP TABLE allergies')];
    }
    return [new AllergyController(new RegisterAllergy($repo, fn () => uniqid('ALG-')), new GetAllergyAlert($repo), new AllergyBannerView()), $probe];
}

function check(string $name, bool $ok): void
{
    global $adapter;
    echo ($ok ? 'OK' : 'FALLO') . ": [{$adapter}] {$name}" . PHP_EOL;
    $ok || exit(1);
}
$penicilina = ['allergen_code' => 'RXN-7980', 'allergen_name' => 'Penicilina', 'reaction' => 'Anafilaxia', 'allergy_type' => 'allergy', 'severity' => 'life_threatening'];
foreach (['PDO/SQLite', 'InMemory'] as $adapter) {
    [$http, $probe] = app($adapter);
    $alert = fn (string $patient, array $query = []) => $http->handle('GET', "/patients/{$patient}/allergy-alert", $query, 'medico1')[1];
    check('paciente sin alergias → nivel none', $alert('P-1')['alert']['level'] === 'none');
    [$status, $body] = $http->handle('POST', '/patients/P-1/allergies', $penicilina, 'medico1');
    check('alta válida → 201', $status === 201 && $body['allergy']['severity'] === 'life_threatening');
    check('el alta queda auditada', $probe['audit']() === 'alergia.registrada');
    check('alergia que amenaza la vida → banner critical', $alert('P-1')['alert']['level'] === 'critical' && count($alert('P-1')['alert']['allergies']) === 1);
    check('misma alergia activa otra vez (otro texto y mayúsculas) → 409', $http->handle('POST', '/patients/P-1/allergies', ['allergen_code' => ' rxn-7980 ', 'severity' => 'mild'] + $penicilina, 'medico1')[0] === 409);
    $http->handle('POST', '/patients/P-2/allergies', ['allergen_code' => null, 'allergen_name' => 'Lactosa <i>', 'allergy_type' => 'intolerance', 'severity' => 'mild'], 'medico1');
    check('intolerancia leve → banner warning', $alert('P-2')['alert']['level'] === 'warning');
    $html = $alert('P-2', ['format' => 'html']);
    check('View HTML: banner con role="alert", nivel y texto escapado', str_contains($html, 'role="alert"') && str_contains($html, 'warning') && str_contains($html, 'Lactosa &lt;i&gt;'));
    foreach ([
        'sin alérgeno → 422 ALLERGEN_REQUIRED' => [['allergen_code' => '', 'allergen_name' => ' '], 'ALLERGEN_REQUIRED'],
        'tipo inválido → 422 ALLERGY_TYPE_INVALID' => [['allergy_type' => 'alergia'], 'ALLERGY_TYPE_INVALID'],
        'severidad inválida → 422 SEVERITY_INVALID' => [['severity' => 'grave'], 'SEVERITY_INVALID'],
    ] as $name => [$change, $rule]) {
        check($name, $http->handle('POST', '/patients/P-2/allergies', array_merge($penicilina, $change), 'medico1')[1]['error'] === $rule);
    }
    check('paciente inexistente → 404 PATIENT_NOT_FOUND', $http->handle('POST', '/patients/P-9/allergies', $penicilina, 'medico1')[0] === 404);
    check('sin usuario → 401', $http->handle('GET', '/patients/P-1/allergy-alert', [], null)[0] === 401);
    check('ningún alta rechazada se guardó', $probe['count']() === 2);
    [$http, $probe] = app($adapter);
    $probe['failWrites']();
    check('falla de persistencia → 500 y la alergia no queda guardada', $http->handle('POST', '/patients/P-1/allergies', $penicilina, 'medico1')[0] === 500 && $probe['count']() === 0);
    $probe['failReads']();
    $unavailable = $http->handle('GET', '/patients/P-1/allergy-alert', ['format' => 'html'], 'medico1')[1];
    check('base sin respuesta → «no disponible», nunca «sin alergias» (falla segura)', str_contains($unavailable, 'Alergias no disponibles') && !str_contains($unavailable, 'Sin alergias'));
}
echo 'Todas las pruebas pasaron.' . PHP_EOL;
