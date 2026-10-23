<?php

declare(strict_types=1);

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Persistence\InMemoryAllergyRepository;
use MicroHis\Alergias\Presentation\AllergyBannerView;
use MicroHis\Alergias\Presentation\AllergyController;
use MicroHis\Alergias\Presentation\ApiKernel;
use MicroHisClient\Alergias\AllergyCheckClient;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../client/AllergyCheckClient.php';

/** Semana 5: servidor API v1 (kernel), cliente y contrato, con el adaptador InMemory y datos ficticios. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$repo = new InMemoryAllergyRepository(['P-1']);
$ready = true;
$kernel = new ApiKernel(new AllergyController(new RegisterAllergy($repo, fn () => uniqid('ALG-')), new GetAllergyAlert($repo), new AllergyBannerView()),
    'HOSP-DEMO', function () use (&$ready) { return $ready; });
$h = ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'medico1', 'x-request-id' => 'req-12345678'];
$penicilina = json_encode(['allergen_name' => 'Penicilina', 'allergy_type' => 'allergy', 'severity' => 'life_threatening']);

[$status, $headers] = $kernel->handle('POST', '/api/v1/patients/P-1/allergies', $h, $penicilina);
check('POST /api/v1/patients/P-1/allergies → 201 JSON y devuelve el X-Request-Id', $status === 201 && $headers['Content-Type'] === 'application/json' && $headers['X-Request-Id'] === 'req-12345678');
[$status, $headers, $body] = $kernel->handle('POST', '/api/v1/patients/P-1/allergies', $h, $penicilina);
check('duplicado → 409 problem+json con code y request_id', $status === 409 && $headers['Content-Type'] === 'application/problem+json'
    && json_decode($body, true)['code'] === 'ALLERGY_ALREADY_ACTIVE' && json_decode($body, true)['request_id'] === 'req-12345678');
check('sin X-Tenant-ID → 400 TENANT_REQUIRED', json_decode($kernel->handle('GET', '/api/v1/patients/P-1/allergy-alert', ['x-user-ref' => 'a'], '')[2], true)['code'] === 'TENANT_REQUIRED');
check('tenant ajeno → 403 TENANT_MISMATCH', $kernel->handle('GET', '/api/v1/patients/P-1/allergy-alert', ['x-tenant-id' => 'HOSP-OTRO'] + $h, '')[0] === 403);
check('cuerpo que no es JSON → 400 INVALID_JSON', json_decode($kernel->handle('POST', '/api/v1/patients/P-1/allergies', $h, '{roto')[2], true)['code'] === 'INVALID_JSON');
check('ruta fuera de /api/v1 → 404', $kernel->handle('GET', '/patients/P-1/allergy-alert', $h, '')[0] === 404);
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
    [$status, , $out] = $kernel->handle($method, (string) parse_url($url, PHP_URL_PATH), $headers, (string) $body);
    return [$status, $out];
};
$client = new AllergyCheckClient('http://alergias', 'HOSP-DEMO', 'prescripciones.svc', $transport);
check('cliente: alergia grave → critical en un intento', $client->alertFor('P-1')['level'] === 'critical' && $client->attempts === 1);
$fail = [503];
check('cliente: un 503 se reintenta una vez y la segunda responde', $client->alertFor('P-1')['level'] === 'critical' && $client->attempts === 2);
$fail = ['timeout', 'timeout'];
check('cliente: sin respuesta dos veces → «no disponible», nunca «sin alergias»', $client->alertFor('P-1') === AllergyCheckClient::UNAVAILABLE && $client->attempts === 2);
$fail = [500];
check('cliente: un 500 no se reintenta y también es «no disponible»', $client->alertFor('P-1')['level'] === 'unavailable' && $client->attempts === 1);
$repo->failReads = true;
check('cliente: base caída en el servidor → el servidor ya responde unavailable', $client->alertFor('P-1')['level'] === 'unavailable');

// Contrato: cada ruta de openapi.yaml existe en el servidor (no responde 404 de ruta).
preg_match_all('#^  (/[\w/{}.-]+):#m', file_get_contents(__DIR__ . '/../openapi.yaml'), $paths);
check('openapi.yaml declara las 4 rutas del servidor', count($paths[1]) === 4);
$ready = true;
$probe = ['/api/v1/patients/{patient}/allergies' => 'POST'];
check('todas las rutas del contrato responden en el servidor', array_filter($paths[1], fn ($p) => $kernel->handle($probe[$p] ?? 'GET', str_replace('{patient}', 'P-1', $p), $h, '{}')[0] === 404) === []);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
