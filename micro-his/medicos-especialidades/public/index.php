<?php

declare(strict_types=1);

use MicroHis\Medicos\Persistence\ConfigScheduleProvider;
use MicroHis\Medicos\Persistence\InMemoryAppointmentReader;
use MicroHis\Medicos\Persistence\InMemoryDoctorRepository;
use MicroHis\Medicos\Persistence\InMemoryPermissionChecker;
use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Presentation\Request;

// Front controller: php -S localhost:8004 -t micro-his/medicos-especialidades/public
// Rutas bajo /api/v1. Con MEDICOS_DSN usa PDO; sin ella usa InMemory con datos ficticios, que se reinician en cada petición.
// RBAC aún no está integrado: los permisos de desarrollo salen de InMemoryPermissionChecker (supuesto SD-MED-10).
require __DIR__ . '/../app.php';

$schedule = new ConfigScheduleProvider([]);
$permissions = new InMemoryPermissionChecker([
    'admin1' => ['medicos.registrar', 'medicos.especialidades.asociar', 'medicos.disponibilidad.ver'],
    'recepcion1' => ['medicos.disponibilidad.ver'],
]);
$log = static fn (array $entry) => error_log(json_encode($entry));
$dsn = getenv('MEDICOS_DSN');
if ($dsn !== false && $dsn !== '') {
    $store = new PdoDoctorStore(new PDO($dsn, getenv('MEDICOS_DB_USER') ?: null, getenv('MEDICOS_DB_PASSWORD') ?: null));
    $api = medicos_api($store, $store, $schedule, $permissions, null, $log);
} else {
    $api = medicos_api(new InMemoryDoctorRepository(['CARD' => ['sp-card', 'active'], 'NEURO' => ['sp-neuro', 'active']]), new InMemoryAppointmentReader(), $schedule, $permissions, null, $log);
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
$raw = (string) file_get_contents('php://input');
$body = $raw === '' ? [] : json_decode($raw, true);
$request = new Request($_SERVER['REQUEST_METHOD'] ?? 'GET', (string) parse_url($uri, PHP_URL_PATH), ['X-User-ID' => $_SERVER['HTTP_X_USER_ID'] ?? ''], $query, is_array($body) ? $body : []);
$response = $api->handle($request, is_array($body));

http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header("$name: $value");
}
echo $response['body'];
