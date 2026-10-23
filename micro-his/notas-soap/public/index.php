<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use MicroHis\NotasSoap\Persistence\PdoIcd10Catalog;
use MicroHis\NotasSoap\Presentation\JsonView;
use MicroHis\NotasSoap\Presentation\Request;

// Front controller: php -S localhost:8011 -t micro-his/notas-soap/public
// Con NOTAS_SOAP_DSN usa PDO; sin ella usa InMemory con datos ficticios, que se reinician en cada petición.
require __DIR__ . '/../app.php';

$dsn = getenv('NOTAS_SOAP_DSN');
if ($dsn !== false && $dsn !== '') {
    $pdo = new PDO($dsn, getenv('NOTAS_SOAP_DB_USER') ?: null, getenv('NOTAS_SOAP_DB_PASSWORD') ?: null);
    $router = notas_soap_router(new PdoClinicalNoteStore($pdo, new PdoAuditTrail($pdo)), new PdoCareAssignmentReader($pdo), new PdoIcd10Catalog($pdo));
} else {
    $catalog = new InMemoryIcd10Catalog(['J18.9' => ['Neumonía, no especificada', true], 'I10' => ['Hipertensión esencial', true]]);
    $router = notas_soap_router(new InMemoryClinicalNoteRepository(new InMemoryAuditTrail()), new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']]), $catalog);
}

$raw = (string) file_get_contents('php://input');
$body = $raw === '' ? [] : json_decode($raw, true);
$response = is_array($body)
    ? $router->dispatch(new Request($_SERVER['REQUEST_METHOD'] ?? 'GET', (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), ['X-User-ID' => $_SERVER['HTTP_X_USER_ID'] ?? ''], $body))
    : (new JsonView())->render(422, ['code' => 'PAYLOAD_INVALID']);
http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header("$name: $value");
}
echo $response['body'];
