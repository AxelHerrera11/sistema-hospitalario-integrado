<?php

declare(strict_types=1);

use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Persistence\InMemoryAdmissionRepository;
use MicroHis\Admision\Presentation\AdmissionController;
use MicroHis\Admision\Presentation\ApiKernel;
use MicroHis\Admision\Presentation\AvailableBedsView;
use MicroHisClient\Admision\AdmissionDeskClient;
use MicroHisClient\Admision\AdmissionUnavailable;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../client/AdmissionDeskClient.php';

/** Semana 5: servidor API v1 (kernel), cliente y contrato, con el adaptador InMemory y datos ficticios. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$repo = new InMemoryAdmissionRepository(['P-1', 'P-2'], ['B-1' => ['bed_id' => 'B-1', 'code' => 'MED-101', 'type' => 'adult', 'status' => 'available'],
    'B-2' => ['bed_id' => 'B-2', 'code' => 'MED-102', 'type' => 'adult', 'status' => 'available']]);
$ready = true;
$kernel = new ApiKernel(new AdmissionController(new AdmitPatient($repo, fn () => date(DATE_ATOM), fn () => uniqid('ID-')), new FindAvailableBeds($repo), new AvailableBedsView()),
    'HOSP-DEMO', function () use (&$ready) { return $ready; });
$h = ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'admision1', 'x-request-id' => 'req-12345678'];
$alta = json_encode(['patient_id' => 'P-1', 'bed_id' => 'B-1', 'reason' => 'Neumonía']);

[$status, $headers] = $kernel->handle('POST', '/api/v1/admissions', $h, $alta);
check('POST /api/v1/admissions → 201 JSON y devuelve el X-Request-Id', $status === 201 && $headers['Content-Type'] === 'application/json' && $headers['X-Request-Id'] === 'req-12345678');
[$status, $headers, $body] = $kernel->handle('POST', '/api/v1/admissions', $h, $alta);
check('paciente ya admitido → 409 problem+json con code y request_id', $status === 409 && $headers['Content-Type'] === 'application/problem+json'
    && json_decode($body, true)['code'] === 'PATIENT_ALREADY_ADMITTED' && json_decode($body, true)['request_id'] === 'req-12345678');
check('sin X-Tenant-ID → 400 TENANT_REQUIRED', json_decode($kernel->handle('GET', '/api/v1/beds/available', ['x-user-ref' => 'a'], '')[2], true)['code'] === 'TENANT_REQUIRED');
check('tenant ajeno → 403 TENANT_MISMATCH', $kernel->handle('GET', '/api/v1/beds/available', ['x-tenant-id' => 'HOSP-OTRO'] + $h, '')[0] === 403);
check('cuerpo que no es JSON → 400 INVALID_JSON', json_decode($kernel->handle('POST', '/api/v1/admissions', $h, '{roto')[2], true)['code'] === 'INVALID_JSON');
check('ruta fuera de /api/v1 → 404', $kernel->handle('GET', '/beds/available', $h, '')[0] === 404);
check('/health → 200 sin autenticación', $kernel->handle('GET', '/health', [], '')[0] === 200);
$ready = false;
check('/health/ready con la base caída → 503 unavailable', $kernel->handle('GET', '/health/ready', [], '')[0] === 503);

// Cliente: transporte en proceso (sin red) que puede fallar a propósito.
$fail = [];
$transport = function (string $method, string $url, array $lines, ?string $body) use ($kernel, &$fail): ?array {
    if ($fail !== []) {
        $next = array_shift($fail);
        return $next === 'timeout' ? null : [$next, '{}'];
    }
    $headers = [];
    foreach ($lines as $line) {
        [$name, $value] = explode(': ', $line, 2);
        $headers[strtolower($name)] = $value;
    }
    [$status, , $out] = $kernel->handle($method, (string) parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY), $headers, (string) $body);
    return [$status, $out];
};
$client = new AdmissionDeskClient('http://admision', 'HOSP-DEMO', 'mostrador1', $transport);
$raises = function (Closure $call, string $expected) use (&$fail, $client): bool {
    try { $call(); } catch (Throwable $e) { return $e instanceof $expected; }
    return false;
};
check('cliente: camas disponibles → MED-102', array_column($client->availableBeds('adult'), 'code') === ['MED-102'] && $client->attempts === 1);
$fail = [503];
check('cliente: GET con un 503 se reintenta una vez', count($client->availableBeds()) === 1 && $client->attempts === 2);
check('cliente: admisión válida → episodio y estancia', $client->admit(['patient_id' => 'P-2', 'bed_id' => 'B-2', 'reason' => 'Fractura'])['bedId'] === 'B-2');
$fail = ['timeout'];
check('cliente: POST sin respuesta NO se reintenta → AdmissionUnavailable (verificar la cama)', $raises(fn () => $client->admit(['patient_id' => 'P-2', 'bed_id' => 'B-1', 'reason' => 'x']), AdmissionUnavailable::class) && $client->attempts === 1);
check('cliente: un 4xx llega como error de negocio', $raises(fn () => $client->admit(['patient_id' => 'P-9', 'bed_id' => 'B-1', 'reason' => 'x']), DomainException::class));

// Contrato: cada ruta de openapi.yaml existe en el servidor (no responde 404 de ruta).
preg_match_all('#^  (/[\w/{}.-]+):#m', file_get_contents(__DIR__ . '/../openapi.yaml'), $paths);
check('openapi.yaml declara las 4 rutas del servidor', count($paths[1]) === 4);
$ready = true;
$routeMissing = fn (string $p) => (json_decode($kernel->handle($p === '/api/v1/admissions' ? 'POST' : 'GET', $p, $h, '{}')[2], true)['code'] ?? '') === 'NOT_FOUND';
check('todas las rutas del contrato existen en el servidor (ningún 404 de ruta)', array_filter($paths[1], $routeMissing) === []);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
