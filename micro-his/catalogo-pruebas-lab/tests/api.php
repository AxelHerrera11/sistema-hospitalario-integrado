<?php

declare(strict_types=1);

use MicroHis\CatalogoLab\Application\FindActiveLabTests;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Persistence\InMemoryLabTestRepository;
use MicroHis\CatalogoLab\Presentation\ApiKernel;
use MicroHis\CatalogoLab\Presentation\LabTestController;
use MicroHis\CatalogoLab\Presentation\LabTestListView;
use MicroHisClient\CatalogoLab\CatalogUnavailable;
use MicroHisClient\CatalogoLab\LabCatalogClient;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../client/LabCatalogClient.php';

/** Semana 5: servidor API v1 (kernel), cliente y contrato, con el adaptador InMemory y datos ficticios. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$repo = new InMemoryLabTestRepository();
$ready = true;
$controller = new LabTestController(new RegisterLabTest($repo, fn () => '2026-10-18', fn () => uniqid('LT-')), new FindActiveLabTests($repo), new LabTestListView());
$kernel = new ApiKernel($controller, 'HOSP-DEMO', function () use (&$ready) { return $ready; });
$h = ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'jefa.lab', 'x-request-id' => 'req-12345678'];
$glucosa = json_encode(['code' => 'GLU', 'name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 1, 'result_type' => 'numeric', 'unit' => 'mg/dL',
    'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400, 'valid_from' => '2026-10-19']);

[$status, $headers, $body] = $kernel->handle('POST', '/api/v1/lab-tests', $h, $glucosa);
check('POST /api/v1/lab-tests → 201 JSON y devuelve el X-Request-Id recibido', $status === 201 && $headers['Content-Type'] === 'application/json' && $headers['X-Request-Id'] === 'req-12345678');
[$status, $headers, $body] = $kernel->handle('POST', '/api/v1/lab-tests', $h, $glucosa);
$problem = json_decode($body, true);
check('error de negocio → 409 problem+json con code y request_id', $status === 409 && $headers['Content-Type'] === 'application/problem+json'
    && $problem['code'] === 'LAB_TEST_VALIDITY_OVERLAP' && $problem['request_id'] === 'req-12345678');
check('sin X-Tenant-ID → 400 TENANT_REQUIRED', json_decode($kernel->handle('GET', '/api/v1/lab-tests', ['x-user-ref' => 'a'], '')[2], true)['code'] === 'TENANT_REQUIRED');
check('tenant ajeno → 403 TENANT_MISMATCH', $kernel->handle('GET', '/api/v1/lab-tests', ['x-tenant-id' => 'HOSP-OTRO'] + $h, '')[0] === 403);
check('cuerpo que no es JSON → 400 INVALID_JSON', json_decode($kernel->handle('POST', '/api/v1/lab-tests', $h, '{roto')[2], true)['code'] === 'INVALID_JSON');
check('ruta fuera de /api/v1 → 404 problem+json', $kernel->handle('GET', '/lab-tests', $h, '')[0] === 404);
check('sin X-Request-Id → el servidor genera uno', strlen($kernel->handle('GET', '/api/v1/lab-tests', ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'a'], '')[1]['X-Request-Id']) === 16);
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
$client = new LabCatalogClient('http://catalogo', 'HOSP-DEMO', 'ordenes.svc', $transport);
check('cliente: pruebas vigentes por búsqueda → GLU v1', array_column($client->activeTests('2026-10-20', 'gluc'), 'version_no') === [1] && $client->attempts === 1);
$fail = [503];
check('cliente: un 503 se reintenta una vez y la segunda responde', count($client->activeTests('2026-10-20')) === 1 && $client->attempts === 2);
$fails = function (array $responses, string $expected) use ($client, &$fail): bool {
    $fail = $responses;
    try { $client->activeTests('2026-10-20'); } catch (Throwable $e) { return $e instanceof $expected; }
    return false;
};
check('cliente: sin respuesta dos veces → CatalogUnavailable (Órdenes no ordena a ciegas)', $fails(['timeout', 'timeout'], CatalogUnavailable::class) && $client->attempts === 2);
check('cliente: un 4xx no se reintenta', $fails([422], DomainException::class) && $client->attempts === 1);

// Contrato: cada ruta de openapi.yaml existe en el servidor (no responde 404 de ruta).
preg_match_all('#^  (/[\w/{}.-]+):#m', file_get_contents(__DIR__ . '/../openapi.yaml'), $paths);
check('openapi.yaml declara /api/v1/lab-tests, /health y /health/ready', $paths[1] === ['/api/v1/lab-tests', '/health', '/health/ready']);
$ready = true;
check('todas las rutas del contrato responden en el servidor', array_filter($paths[1], fn ($p) => $kernel->handle('GET', $p, $h, '')[0] === 404) === []);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
