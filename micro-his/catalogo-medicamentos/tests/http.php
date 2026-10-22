<?php

declare(strict_types=1);

// Entrada MVC. (1) Router sobre el adaptador InMemory, sin servidor. (2) HTTP real: php -S en un puerto libre
// con una base SQLite temporal propia de esta ejecución, eliminada al terminar.
// Uso: php micro-his/catalogo-medicamentos/tests/http.php

use MicroHis\MedicationCatalog\Persistence\InMemoryMedicationRepository;
use MicroHis\MedicationCatalog\Presentation\Request;

require __DIR__ . '/harness.php';
require __DIR__ . '/../app.php';

$alta = '{"code":"amx-500","name":"Amoxicilina 500 mg","active_ingredient":"Amoxicilina","pharmacological_group":"Antibiótico"}';

echo "== Router (InMemory) ==\n";
$router = catalog_router(new InMemoryMedicationRepository());
$call = static fn (string $method, string $path, array $query = [], string $body = ''): array => $router->dispatch(new Request($method, $path, $query, $body));
$created = $call('POST', '/medications', [], $alta);
check('POST /medications: 201 y vigencia active', $created['status'] === 201 && $created['body']['medication']['validity'] === 'active');
check('POST con el mismo código: 409', $call('POST', '/medications', [], $alta)['status'] === 409);
check('POST con cuerpo que no es objeto JSON: 400 sin llegar al caso de uso', $call('POST', '/medications', [], '["x"]')['body']['error'] === 'BODY_INVALID'
    && $call('POST', '/medications', [], 'no-json')['status'] === 400);
check('POST con un campo no textual: 422 con el campo', $call('POST', '/medications', [], '{"code":5}')['body']['field'] === 'code');
check('POST con un campo faltante: 422 desde el dominio', $call('POST', '/medications', [], '{"code":"X-1"}')['body']['field'] === 'name');
check('GET /medications?q=: 200 con resultados', $call('GET', '/medications', ['q' => 'amox'])['body']['count'] === 1);
check('GET /medications sin q o con q no textual: 422', $call('GET', '/medications')['status'] === 422 && $call('GET', '/medications', ['q' => ['x']])['status'] === 422);
$id = $created['body']['medication']['id'];
check('PATCH /medications/{id}/validity: 200 inactive', $call('PATCH', "/medications/{$id}/validity", [], '{"validity":"inactive"}')['body']['medication']['validity'] === 'inactive');
check('GET /medications/{id}/validity: lee inactive', $call('GET', "/medications/{$id}/validity")['body']['validity'] === 'inactive');
check('PATCH con validity no textual: 422', $call('PATCH', "/medications/{$id}/validity", [], '{"validity":true}')['status'] === 422);
check('PATCH sobre id inexistente: 404', $call('PATCH', '/medications/99/validity', [], '{"validity":"active"}')['status'] === 404);
check('método no permitido: 405; ruta desconocida: 404', $call('DELETE', "/medications/{$id}/validity")['status'] === 405
    && $call('GET', '/otra')['body']['error'] === 'ROUTE_NOT_FOUND');

echo "\n== HTTP real (php -S y SQLite temporal) ==\n";
$database = tempnam(sys_get_temp_dir(), 'catalogo-http-');
$init = proc_open([PHP_BINARY, __DIR__ . '/../bin/catalogo.php', $database, 'init'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
proc_close($init);
$socket = stream_socket_server('tcp://127.0.0.1:0');
$port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
fclose($socket);
$devNull = PHP_OS_FAMILY === 'Windows' ? 'nul' : '/dev/null';
$server = proc_open([PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/../public/index.php'], [1 => ['file', $devNull, 'w'], 2 => ['file', $devNull, 'w']],
    $pipes, null, ['MICROHIS_CATALOG_DB' => $database] + getenv());
$http = static function (string $method, string $path, string $body = '') use ($port): array {
    $context = stream_context_create(['http' => ['method' => $method, 'header' => "Content-Type: application/json\r\n", 'content' => $body, 'ignore_errors' => true, 'timeout' => 5]]);
    $raw = @file_get_contents("http://127.0.0.1:{$port}{$path}", false, $context);
    preg_match('#HTTP/\S+ (\d{3})#', $http_response_header[0] ?? '', $status);

    return [(int) ($status[1] ?? 0), json_decode((string) $raw, true)];
};
for ($i = 0; $i < 50 && $http('GET', '/salud')[0] === 0; $i++) {
    usleep(100_000);
}
[$status, $body] = $http('POST', '/medications', $alta);
check('HTTP POST real: 201 y el registro queda en la base SQLite', $status === 201 && $body['medication']['pharmacological_group'] === 'Antibiótico');
check('HTTP GET real de búsqueda: 200 con el registro persistido', $http('GET', '/medications?q=amox')[1]['count'] === 1);
[$status, $body] = $http('PATCH', "/medications/{$body['medication']['id']}/validity", '{"validity":"inactive"}');
check('HTTP PATCH real: 200 inactive', $status === 200 && $body['medication']['validity'] === 'inactive');
check('HTTP con JSON inválido: 400', $http('POST', '/medications', '{')[0] === 400);
proc_terminate($server);
proc_close($server);
@unlink($database);
check('la base temporal de la prueba se eliminó', !is_file($database));

finish();
