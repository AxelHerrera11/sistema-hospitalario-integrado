<?php

declare(strict_types=1);

use MicroHis\Medicos\Persistence\ConfigScheduleProvider;
use MicroHis\Medicos\Persistence\InMemoryAppointmentReader;
use MicroHis\Medicos\Persistence\InMemoryDoctorRepository;
use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Presentation\JsonView;
use MicroHis\Medicos\Presentation\Request;

// Front controller: php -S localhost:8004 -t micro-his/medicos-especialidades/public
// Con MEDICOS_DSN usa PDO; sin ella usa InMemory con datos ficticios, que se reinician en cada petición.
require __DIR__ . '/../app.php';

$schedule = new ConfigScheduleProvider([]);
$dsn = getenv('MEDICOS_DSN');
if ($dsn !== false && $dsn !== '') {
    $store = new PdoDoctorStore(new PDO($dsn, getenv('MEDICOS_DB_USER') ?: null, getenv('MEDICOS_DB_PASSWORD') ?: null));
    $router = medicos_router($store, $store, $schedule);
} else {
    $router = medicos_router(new InMemoryDoctorRepository(['CARD' => ['sp-card', 'active'], 'NEURO' => ['sp-neuro', 'active']]), new InMemoryAppointmentReader(), $schedule);
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
$raw = (string) file_get_contents('php://input');
$body = $raw === '' ? [] : json_decode($raw, true);
$response = is_array($body)
    ? $router->dispatch(new Request($_SERVER['REQUEST_METHOD'] ?? 'GET', (string) parse_url($uri, PHP_URL_PATH), ['X-User-ID' => $_SERVER['HTTP_X_USER_ID'] ?? ''], $query, $body))
    : (new JsonView())->render(422, ['code' => 'PAYLOAD_INVALID']);

http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header("$name: $value");
}
echo $response['body'];
