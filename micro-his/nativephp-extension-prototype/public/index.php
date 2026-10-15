<?php

declare(strict_types=1);

use MicroHis\NativePhpPrototype\Application\PrepareLocalAction;
use MicroHis\NativePhpPrototype\Application\SyncLocalActions;
use MicroHis\NativePhpPrototype\Persistence\ArrayTelemetryRecorder;
use MicroHis\NativePhpPrototype\Persistence\FakeSyncGateway;
use MicroHis\NativePhpPrototype\Persistence\InMemoryLocalActionRepository;
use MicroHis\NativePhpPrototype\Presentation\PrototypeController;

require_once __DIR__ . '/../bootstrap.php';

$repository = new InMemoryLocalActionRepository();
$telemetry = new ArrayTelemetryRecorder();
$controller = new PrototypeController(
    new PrepareLocalAction($repository, $telemetry),
    new SyncLocalActions($repository, new FakeSyncGateway(), $telemetry),
);

$action = $_POST['action'] ?? 'prepare';

header('Content-Type: application/json');
echo json_encode($action === 'sync' ? $controller->sync($_POST) : $controller->prepare($_POST), JSON_PRETTY_PRINT);
