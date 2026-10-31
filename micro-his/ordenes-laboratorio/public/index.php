<?php

declare(strict_types=1);

use MicroHis\OrdenesLab\Application\AdvanceLabOrder;
use MicroHis\OrdenesLab\Application\CancelLabOrder;
use MicroHis\OrdenesLab\Application\FindLabOrder;
use MicroHis\OrdenesLab\Application\IssueLabOrder;
use MicroHis\OrdenesLab\Application\ListLabOrders;
use MicroHis\OrdenesLab\Persistence\PdoLabOrderRepository;
use MicroHis\OrdenesLab\Presentation\ApiKernel;
use MicroHis\OrdenesLab\Presentation\LabOrderController;
use MicroHis\OrdenesLab\Presentation\LabOrderPresenter;
use MicroHis\OrdenesLab\Presentation\LabOrderView;
use MicroHis\OrdenesLab\Tests\Fixtures\InMemoryExpedienteGateway;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../tests/fixtures/InMemoryExpedienteGateway.php';

$newId = static fn (): string => bin2hex(random_bytes(8));
$now = static fn (): string => date('c');
$dsn = (string) (getenv('MICROHIS_DSN') ?: '');
if ($dsn === '') {
    $dsn = 'sqlite:' . (string) (getenv('MICROHIS_DEMO_DB') ?: sys_get_temp_dir() . '/microhis-ordenes-demo.sqlite');
}
$pdo = new PDO($dsn, getenv('MICROHIS_DB_USER') ?: null, getenv('MICROHIS_DB_PASSWORD') ?: null);
if (str_starts_with($dsn, 'sqlite:')) {
    $pdo->exec('PRAGMA foreign_keys = ON');
    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'lab_orders'")->fetchColumn();
    if ($exists === false) {
        $pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    }
}
$repository = new PdoLabOrderRepository($pdo);

$demoTenant = (string) (getenv('MICROHIS_DEMO_TENANT') ?: 'hosp-a');
$gateway = new InMemoryExpedienteGateway(
    [$demoTenant . '|pat-1|epi-1' => ['patient_id' => 'pat-1', 'episode_id' => 'epi-1', 'open' => true, 'clinical_note_ids' => ['note-1']]],
    [$demoTenant => [
        'test-glu' => ['code' => 'GLU', 'name' => 'Glucosa'],
        'test-hb' => ['code' => 'HB', 'name' => 'Hemograma'],
        'test-tsh' => ['code' => 'TSH', 'name' => 'Hormona tiroestimulante'],
    ]],
);

$kernel = new ApiKernel(
    new LabOrderController(
        new IssueLabOrder($repository, $gateway, $newId, $newId, $now, $newId),
        new AdvanceLabOrder($repository, $newId, $now),
        new CancelLabOrder($repository, $newId, $now),
        new FindLabOrder($repository),
        new ListLabOrders($repository),
        new LabOrderPresenter(),
        new LabOrderView(),
    ),
    static fn (): string => bin2hex(random_bytes(16)),
);

$headers = function_exists('getallheaders') ? getallheaders() : [];
if ($headers === []) {
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_')) {
            $headers[str_replace('_', '-', substr($key, 5))] = (string) $value;
        }
    }
}

$raw = (string) file_get_contents('php://input');
$input = [];
if (trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $requestId = bin2hex(random_bytes(16));
        http_response_code(400);
        header('Content-Type: application/problem+json; charset=utf-8');
        header('X-Request-ID: ' . $requestId);
        echo json_encode(['type' => 'about:blank', 'title' => 'INVALID_JSON', 'status' => 400, 'code' => 'INVALID_JSON', 'request_id' => $requestId], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $input = $decoded;
}

$response = $kernel->handle(
    (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH),
    $headers,
    $input,
);

http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header($name . ': ' . $value);
}
echo is_string($response['body'])
    ? $response['body']
    : json_encode($response['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
