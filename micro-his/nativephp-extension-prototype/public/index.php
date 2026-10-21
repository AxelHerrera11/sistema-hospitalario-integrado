<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

foreach ([
    '/../src/Persistence/InMemoryLocalActionRepository.php',
    '/../src/Persistence/PdoLocalActionRepository.php',
    '/../src/Persistence/ArrayTelemetryRecorder.php',
    '/../src/Persistence/FakeSyncGateway.php',
    '/../src/Presentation/PrototypeController.php',
] as $adapter) {
    if (is_file(__DIR__ . $adapter)) {
        require_once __DIR__ . $adapter;
    }
}

header('Content-Type: application/json');

if (!class_exists('MicroHis\\NativePhpPrototype\\Presentation\\PrototypeController')) {
    echo json_encode([
        'status' => 501,
        'body' => [
            'error' => 'El nucleo esta cargado; los adaptadores y la presentacion se incorporan en la parte siguiente.',
        ],
    ], JSON_PRETTY_PRINT);
    return;
}

$repository = new \MicroHis\NativePhpPrototype\Persistence\InMemoryLocalActionRepository();
$telemetry = new \MicroHis\NativePhpPrototype\Persistence\ArrayTelemetryRecorder();
$controller = new \MicroHis\NativePhpPrototype\Presentation\PrototypeController(
    new \MicroHis\NativePhpPrototype\Application\PrepareLocalAction($repository, $telemetry),
    new \MicroHis\NativePhpPrototype\Application\SyncLocalActions($repository, new \MicroHis\NativePhpPrototype\Persistence\FakeSyncGateway(), $telemetry),
);
$action = $_POST['action'] ?? 'prepare';
$response = $action === 'sync' ? $controller->sync($_POST) : $controller->prepare($_POST);
http_response_code($response['status']);
echo json_encode($response, JSON_PRETTY_PRINT);
