<?php

declare(strict_types=1);

$root = __DIR__ . '/src';

foreach ([
    '/Domain/DomainRuleViolation.php',
    '/Domain/ConnectivityStatus.php',
    '/Domain/SyncStatus.php',
    '/Domain/LocalHisAction.php',
    '/Domain/SyncPolicy.php',
    '/Application/LocalActionRepository.php',
    '/Application/TelemetryRecorder.php',
    '/Application/SyncGateway.php',
    '/Application/PrepareLocalActionCommand.php',
    '/Application/SyncLocalActionsCommand.php',
    '/Application/PrepareLocalAction.php',
    '/Application/SyncLocalActions.php',
    '/Persistence/InMemoryLocalActionRepository.php',
    '/Persistence/PdoLocalActionRepository.php',
    '/Persistence/ArrayTelemetryRecorder.php',
    '/Persistence/FakeSyncGateway.php',
    '/Presentation/PrototypeController.php',
] as $file) {
    require_once $root . $file;
}
