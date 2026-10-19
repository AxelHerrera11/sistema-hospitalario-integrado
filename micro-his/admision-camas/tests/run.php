<?php

declare(strict_types=1);

use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Persistence\InMemoryAdmissionRepository;
use MicroHis\Admision\Persistence\PdoAdmissionRepository;
use MicroHis\Admision\Presentation\AdmissionController;
use MicroHis\Admision\Presentation\AvailableBedsView;

require __DIR__ . '/../bootstrap.php';

/**
 * Prueba de contrato: la misma batería corre con los dos adaptadores del Repository.
 * $probe da lo que cada uno expone distinto: estado de una cama, estancias, último evento auditado y cómo simular la falla.
 */
function app(string $adapter): array
{
    $beds = [['B-101', 'MED-101', 'adult', 'available'], ['B-102', 'MED-102', 'adult', 'available'], ['B-201', 'PED-201', 'pediatric', 'available'], ['B-301', 'MED-301', 'adult', 'maintenance']];
    if ($adapter === 'InMemory') {
        $repo = new InMemoryAdmissionRepository(['P-1', 'P-2'], array_column(array_map(fn ($b) => array_combine(['bed_id', 'code', 'type', 'status'], $b), $beds), null, 'bed_id'));
        $probe = ['bed' => fn (string $id) => $repo->beds[$id]['status'], 'stays' => fn () => count($repo->admissions),
            'audit' => fn () => $repo->audit[0][0] ?? null, 'fail' => fn () => $repo->failWrites = true];
    } else {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
        $pdo->exec("INSERT INTO local_patients VALUES ('P-1'), ('P-2')");
        foreach ($beds as $bed) {
            $pdo->prepare('INSERT INTO beds VALUES (?, ?, ?, ?)')->execute($bed);
        }
        $repo = new PdoAdmissionRepository($pdo);
        $bedStatus = $pdo->prepare('SELECT status FROM beds WHERE bed_id = ?');
        $probe = ['bed' => fn (string $id) => $bedStatus->execute([$id]) ? $bedStatus->fetchColumn() : null,
            'stays' => fn () => (int) $pdo->query('SELECT COUNT(*) FROM bed_stays')->fetchColumn(),
            'audit' => fn () => $pdo->query('SELECT action FROM local_access_audits')->fetchColumn(), 'fail' => fn () => $pdo->exec('DROP TABLE local_access_audits')];
    }
    $admit = new AdmitPatient($repo, fn () => '2026-10-18T09:00:00-06:00', fn () => uniqid('ID-'));
    return [new AdmissionController($admit, new FindAvailableBeds($repo), new AvailableBedsView()), $probe];
}

function check(string $name, bool $ok): void
{
    global $adapter;
    echo ($ok ? 'OK' : 'FALLO') . ": [{$adapter}] {$name}" . PHP_EOL;
    $ok || exit(1);
}
$admision = ['patient_id' => 'P-1', 'bed_id' => 'B-101', 'bed_type' => 'adult', 'reason' => 'Neumonía, requiere oxígeno'];
foreach (['PDO/SQLite', 'InMemory'] as $adapter) {
    [$http, $probe] = app($adapter);
    [$status, $body] = $http->handle('POST', '/admissions', $admision, 'admision1');
    check('admisión válida → 201 con episodio y estancia', $status === 201 && $body['admission']['bedId'] === 'B-101');
    check('la cama queda ocupada', $probe['bed']('B-101') === 'occupied');
    check('la asignación queda auditada', $probe['audit']() === 'admision.cama_asignada');
    check('otra admisión a la misma cama → 409 BED_NOT_AVAILABLE', $http->handle('POST', '/admissions', ['patient_id' => 'P-2'] + $admision, 'admision1')[1]['error'] === 'BED_NOT_AVAILABLE');
    check('el mismo paciente en otra cama → 409 PATIENT_ALREADY_ADMITTED', $http->handle('POST', '/admissions', ['bed_id' => 'B-102'] + $admision, 'admision1')[1]['error'] === 'PATIENT_ALREADY_ADMITTED');
    foreach ([
        'cama en mantenimiento → 409 BED_NOT_AVAILABLE' => [['bed_id' => 'B-301'], 409, 'BED_NOT_AVAILABLE'],
        'cama pediátrica para un adulto → 422 BED_TYPE_MISMATCH' => [['bed_id' => 'B-201'], 422, 'BED_TYPE_MISMATCH'],
        'sin motivo → 422 REASON_REQUIRED' => [['bed_id' => 'B-102', 'reason' => '  '], 422, 'REASON_REQUIRED'],
        'paciente inexistente → 404 PATIENT_NOT_FOUND' => [['patient_id' => 'P-9'], 404, 'PATIENT_NOT_FOUND'],
        'cama inexistente → 404 BED_NOT_FOUND' => [['bed_id' => 'B-999'], 404, 'BED_NOT_FOUND'],
    ] as $name => [$change, $code, $rule]) {
        [$status, $body] = $http->handle('POST', '/admissions', array_merge($admision, ['patient_id' => 'P-2'], $change), 'admision1');
        check($name, $status === $code && $body['error'] === $rule);
    }
    check('sin usuario → 401', $http->handle('POST', '/admissions', $admision, null)[0] === 401);
    check('ninguna admisión rechazada dejó estancias', $probe['stays']() === 1);
    [$status, $body] = $http->handle('GET', '/beds/available', ['type' => 'adult'], 'admision1');
    check('camas adultas disponibles → solo MED-102', $status === 200 && array_column($body['beds'], 'code') === ['MED-102']);
    $html = $http->handle('GET', '/beds/available', ['type' => 'pediatric<x>', 'format' => 'html'], 'admision1')[1];
    check('View HTML: sin camas sugiere lista de espera y escapa el filtro', str_contains($html, 'lista de espera') && str_contains($html, 'pediatric&lt;x&gt;'));
    [$http, $probe] = app($adapter);
    $probe['fail']();
    check('falla de persistencia → 500 PERSISTENCE_FAILURE', $http->handle('POST', '/admissions', $admision, 'admision1')[0] === 500);
    check('la cama sigue disponible y no quedó estancia', $probe['bed']('B-101') === 'available' && $probe['stays']() === 0);
}
echo 'Todas las pruebas pasaron.' . PHP_EOL;
