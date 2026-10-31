<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Domain\Uuid;
use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;
use MicroHis\NotasSoap\Persistence\InMemoryPermissionChecker;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use MicroHis\NotasSoap\Persistence\PdoIcd10Catalog;
use MicroHis\NotasSoap\Presentation\Request;

// Front controller del API v1: php -S localhost:8011 -t micro-his/notas-soap/public
// Con NOTAS_SOAP_DSN usa PDO; sin ella usa InMemory con datos ficticios, que se reinician en cada petición.
// RBAC es un doble en memoria hasta integrar ASII-02.
require __DIR__ . '/../app.php';

$traceId = Uuid::v4(); // el mismo id va en la respuesta y en correlation_id de la auditoría
$permissions = new InMemoryPermissionChecker([
    'dra.lopez' => ['notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.ver'],
    'enf.ruiz' => ['notas_soap.ver'],
]);
$dsn = getenv('NOTAS_SOAP_DSN');
if ($dsn !== false && $dsn !== '') {
    $pdo = new PDO($dsn, getenv('NOTAS_SOAP_DB_USER') ?: null, getenv('NOTAS_SOAP_DB_PASSWORD') ?: null);
    $parts = [new PdoClinicalNoteStore($pdo, new PdoAuditTrail($pdo, $traceId)), new PdoCareAssignmentReader($pdo), new PdoIcd10Catalog($pdo)];
} else {
    $catalog = new InMemoryIcd10Catalog(['J18.9' => ['Neumonía, no especificada', true], 'I10' => ['Hipertensión esencial', true]]);
    $parts = [new InMemoryClinicalNoteRepository(new InMemoryAuditTrail()), new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez', 'enf.ruiz']]), $catalog];
}
// Bitácora de acceso en stderr, una línea JSON por petición y sin el cuerpo (observabilidad sin datos clínicos).
$log = static fn (array $entry) => error_log(json_encode(['event' => 'api_access'] + $entry, JSON_UNESCAPED_SLASHES));
$api = notas_soap_api(...$parts, permissions: $permissions, traceIds: static fn (): string => $traceId, log: $log);

$raw = (string) file_get_contents('php://input');
$body = $raw === '' ? [] : json_decode($raw, true);
$response = $api->handle(new Request($_SERVER['REQUEST_METHOD'] ?? 'GET', (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), ['X-User-ID' => $_SERVER['HTTP_X_USER_ID'] ?? ''], is_array($body) ? $body : []), is_array($body));
http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header("$name: $value");
}
echo $response['body'];
