<?php

declare(strict_types=1);

use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\GetInbox;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Persistence\InMemoryAlertRepository;
use MicroHis\Alertas\Presentation\AlertInboxView;
use MicroHis\Alertas\Presentation\ApiKernel;
use MicroHis\Alertas\Presentation\CriticalAlertController;
use MicroHisClient\Alertas\CriticalAlertsClient;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../client/CriticalAlertsClient.php';

/** Semana 5: servidor API v1 (kernel), cliente y contrato, con el adaptador InMemory y datos ficticios. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$repo = new InMemoryAlertRepository(['GLU-v1' => ['critical_low' => 40.0, 'critical_high' => 400.0]], [['EP-1', 'P-1', 'dra.lopez']]);
$ready = true;
$controller = new CriticalAlertController(new HandleResultValidated($repo, fn () => new DateTimeImmutable(), fn () => uniqid('ALR-')),
    new AcknowledgeAlert($repo), new GetInbox($repo), new AlertInboxView());
$kernel = new ApiKernel($controller, 'HOSP-DEMO', function () use (&$ready) { return $ready; });
$h = ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'svc.validacion', 'x-request-id' => 'req-12345678'];
$event = ['result_version_id' => 'RV-1', 'lab_test_id' => 'GLU-v1', 'patient_id' => 'P-1', 'episode_id' => 'EP-1', 'value' => 32];

[$status, $headers, $body] = $kernel->handle('POST', '/api/v1/internal/result-validated', $h, json_encode($event));
$alertId = json_decode($body, true)['alert']['id'];
check('POST /api/v1/internal/result-validated → 201 created y devuelve el X-Request-Id', $status === 201 && $headers['X-Request-Id'] === 'req-12345678');
[$status, $headers, $body] = $kernel->handle('POST', "/api/v1/critical-alerts/{$alertId}/acknowledge", ['x-user-ref' => 'dra.lopez'] + $h, json_encode(['read_back' => ['value' => 1]]));
check('lectura incorrecta → 422 problem+json con code y request_id', $status === 422 && $headers['Content-Type'] === 'application/problem+json'
    && json_decode($body, true)['code'] === 'READ_BACK_MISMATCH' && json_decode($body, true)['request_id'] === 'req-12345678');
check('sin X-Tenant-ID → 400 TENANT_REQUIRED', json_decode($kernel->handle('GET', '/api/v1/critical-alerts', ['x-user-ref' => 'a'], '')[2], true)['code'] === 'TENANT_REQUIRED');
check('tenant ajeno → 403 TENANT_MISMATCH', $kernel->handle('GET', '/api/v1/critical-alerts', ['x-tenant-id' => 'HOSP-OTRO'] + $h, '')[0] === 403);
check('cuerpo que no es JSON → 400 INVALID_JSON', json_decode($kernel->handle('POST', '/api/v1/internal/result-validated', $h, '{roto')[2], true)['code'] === 'INVALID_JSON');
check('/health → 200 y /health/ready con la base caída → 503', $kernel->handle('GET', '/health', [], '')[0] === 200 && ($ready = false) === false
    && $kernel->handle('GET', '/health/ready', [], '')[0] === 503);

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
    [$status, , $out] = $kernel->handle($method, (string) parse_url($url, PHP_URL_PATH), $headers, (string) $body);
    return [$status, $out];
};
$publisher = new CriticalAlertsClient('http://alertas', 'HOSP-DEMO', 'svc.validacion', $transport);
$fail = [500];
check('publicar: un 500 se reintenta (el servidor deduplica) y el evento repetido da duplicate', $publisher->publish($event) === 'duplicate' && $publisher->attempts === 2);
$fail = ['timeout', 'timeout'];
check('publicar: sin respuesta dos veces → retry_later (queda en el inbox)', $publisher->publish(['result_version_id' => 'RV-2'] + $event) === 'retry_later' && $publisher->attempts === 2);
check('publicar: el siguiente ciclo crea la alerta una sola vez', $publisher->publish(['result_version_id' => 'RV-2'] + $event) === 'created' && $publisher->publish(['result_version_id' => 'RV-2'] + $event) === 'duplicate');
try {
    $publisher->publish(['value' => null] + $event);
    check('publicar: evento inválido → error de negocio, no se reintenta', false);
} catch (DomainException $e) {
    check('publicar: evento inválido → error de negocio, no se reintenta', $e->getMessage() === 'EVENT_INVALID' && $publisher->attempts === 1);
}
$doctor = new CriticalAlertsClient('http://alertas', 'HOSP-DEMO', 'dra.lopez', $transport);
check('bandeja: la médica ve sus 2 alertas pendientes', count($doctor->inbox()) === 2);
$fail = ['timeout', 'timeout'];
check('bandeja: sin respuesta → null («no disponible»), nunca una bandeja vacía', $doctor->inbox() === null);

// Contrato: cada ruta de openapi.yaml existe en el servidor (ningún 404 de ruta).
preg_match_all('#^  (/[\w/{}.-]+):#m', file_get_contents(__DIR__ . '/../openapi.yaml'), $paths);
check('openapi.yaml declara las 5 rutas del servidor', count($paths[1]) === 5);
$ready = true;
$routeMissing = fn (string $p) => (json_decode($kernel->handle(str_contains($p, 'internal') || str_contains($p, 'acknowledge') ? 'POST' : 'GET',
    str_replace('{alert}', $alertId, $p), $h, '{}')[2], true)['code'] ?? '') === 'NOT_FOUND';
check('todas las rutas del contrato existen en el servidor', array_filter($paths[1], $routeMissing) === []);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
