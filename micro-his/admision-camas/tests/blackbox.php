<?php

declare(strict_types=1);

use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Persistence\InMemoryAdmissionRepository;
use MicroHis\Admision\Presentation\AdmissionController;
use MicroHis\Admision\Presentation\ApiKernel;
use MicroHis\Admision\Presentation\AvailableBedsView;

require __DIR__ . '/../bootstrap.php';

/** Caja negra: solo el contrato HTTP de openapi.yaml, con particiones de equivalencia y valores límite. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
function server(): ApiKernel
{
    $beds = [];
    foreach ([['B-1', 'MED-101', 'adult', 'available'], ['B-2', 'PED-201', 'pediatric', 'available'], ['B-3', 'MED-103', 'adult', 'maintenance']] as [$id, $code, $type, $status]) {
        $beds[$id] = ['bed_id' => $id, 'code' => $code, 'type' => $type, 'status' => $status];
    }
    $repo = new InMemoryAdmissionRepository(['P-1', 'P-2'], $beds);
    return new ApiKernel(new AdmissionController(new AdmitPatient($repo, fn () => 'T', fn () => uniqid()), new FindAvailableBeds($repo), new AvailableBedsView()), 'HOSP-DEMO', fn () => true);
}
$h = ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'admision1'];
$post = function (ApiKernel $k, array $change) use ($h): array {
    [$status, , $out] = $k->handle('POST', '/api/v1/admissions', $h, json_encode(array_merge(['patient_id' => 'P-1', 'bed_id' => 'B-1', 'reason' => 'Neumonía'], $change)));
    return [$status, json_decode($out, true)['code'] ?? null];
};
$beds = fn (ApiKernel $k, string $type) => array_column(json_decode($k->handle('GET', '/api/v1/beds/available?type=' . $type, $h, '')[2], true)['beds'], 'code');
// Partición: type en la búsqueda (vacío, existente sin disponibles, existente con disponibles, inexistente).
check('type vacío → todas las disponibles', $beds(server(), '') === ['MED-101', 'PED-201']);
check('type adult → solo MED-101 (MED-103 está en mantenimiento)', $beds(server(), 'adult') === ['MED-101']);
check('type inexistente → lista vacía (200)', $beds(server(), 'isolation') === []);
// Valores límite de reason (minLength: 1).
check('reason de 1 carácter → 201', $post(server(), ['reason' => 'x'])[0] === 201);
check('reason vacío → 422 REASON_REQUIRED', $post(server(), ['reason' => '']) === [422, 'REASON_REQUIRED']);
// Partición: estado de la cama.
check('cama en mantenimiento → 409 BED_NOT_AVAILABLE', $post(server(), ['bed_id' => 'B-3']) === [409, 'BED_NOT_AVAILABLE']);
check('cama inexistente → 404 BED_NOT_FOUND', $post(server(), ['bed_id' => 'B-9']) === [404, 'BED_NOT_FOUND']);
// Partición: bed_type pedido (ausente, igual, distinto).
check('bed_type ausente con cama pediátrica → 201', $post(server(), ['bed_id' => 'B-2'])[0] === 201);
check('bed_type adult con cama pediátrica → 422 BED_TYPE_MISMATCH', $post(server(), ['bed_id' => 'B-2', 'bed_type' => 'adult']) === [422, 'BED_TYPE_MISMATCH']);
// Secuencia: la misma cama dos veces y el mismo paciente dos veces.
$k = server();
$post($k, []);
check('otra persona en la cama ya ocupada → 409 BED_NOT_AVAILABLE', $post($k, ['patient_id' => 'P-2']) === [409, 'BED_NOT_AVAILABLE']);
check('el mismo paciente en otra cama → 409 PATIENT_ALREADY_ADMITTED', $post($k, ['bed_id' => 'B-2']) === [409, 'PATIENT_ALREADY_ADMITTED']);
check('paciente inexistente → 404 PATIENT_NOT_FOUND', $post(server(), ['patient_id' => 'P-9']) === [404, 'PATIENT_NOT_FOUND']);
echo 'Todas las pruebas de caja negra pasaron.' . PHP_EOL;
