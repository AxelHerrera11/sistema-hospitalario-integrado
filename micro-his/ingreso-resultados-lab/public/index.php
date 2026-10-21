<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

foreach ([
    '/../src/Persistence/InMemoryPendingResultRepository.php',
    '/../src/Persistence/PdoPendingResultRepository.php',
    '/../src/Persistence/ArrayAuditLogger.php',
    '/../src/Presentation/HttpResultController.php',
] as $adapter) {
    if (is_file(__DIR__ . $adapter)) {
        require_once __DIR__ . $adapter;
    }
}

header('Content-Type: application/json');

if (!class_exists('MicroHis\\IngresoResultados\\Presentation\\HttpResultController')) {
    echo json_encode([
        'status' => 501,
        'body' => [
            'error' => 'El nucleo esta cargado; el adaptador HTTP se incorpora en la parte de persistencia y pruebas.',
        ],
    ], JSON_PRETTY_PRINT);
    return;
}

$repository = new \MicroHis\IngresoResultados\Persistence\InMemoryPendingResultRepository([
    new \MicroHis\IngresoResultados\Domain\PendingLabResult('res-001', 'hospital-demo', 'PAC-001', 'GLU', 'mg/dL'),
]);
$audit = new \MicroHis\IngresoResultados\Persistence\ArrayAuditLogger();
$controller = new \MicroHis\IngresoResultados\Presentation\HttpResultController(
    new \MicroHis\IngresoResultados\Application\CapturePendingResult($repository, $audit),
);
$response = $controller->capture($_POST);
http_response_code($response['status']);
echo json_encode($response, JSON_PRETTY_PRINT);
