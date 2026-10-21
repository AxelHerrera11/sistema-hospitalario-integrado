<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

foreach ([
    '/../src/Persistence/InMemoryReviewResultRepository.php',
    '/../src/Persistence/PdoReviewResultRepository.php',
    '/../src/Persistence/ArrayReviewAuditLogger.php',
    '/../src/Presentation/HttpReviewController.php',
] as $adapter) {
    if (is_file(__DIR__ . $adapter)) {
        require_once __DIR__ . $adapter;
    }
}

header('Content-Type: application/json');

if (!class_exists('MicroHis\\ValidacionResultados\\Presentation\\HttpReviewController')) {
    echo json_encode([
        'status' => 501,
        'body' => [
            'error' => 'El nucleo esta cargado; el adaptador HTTP se incorpora en la parte de persistencia y pruebas.',
        ],
    ], JSON_PRETTY_PRINT);
    return;
}

$repository = new \MicroHis\ValidacionResultados\Persistence\InMemoryReviewResultRepository([
    new \MicroHis\ValidacionResultados\Domain\ReviewableLabResult('rev-001', 'hospital-demo', 'PAC-001', 'GLU', '95', 'mg/dL', 'mg/dL'),
]);
$audit = new \MicroHis\ValidacionResultados\Persistence\ArrayReviewAuditLogger();
$controller = new \MicroHis\ValidacionResultados\Presentation\HttpReviewController(
    new \MicroHis\ValidacionResultados\Application\ValidateReviewedResult($repository, $audit),
    new \MicroHis\ValidacionResultados\Application\RejectReviewedResult($repository, $audit),
);
$action = $_POST['action'] ?? 'validate';
$response = $action === 'reject' ? $controller->reject($_POST) : $controller->validate($_POST);
http_response_code($response['status']);
echo json_encode(
    $response,
    JSON_PRETTY_PRINT
);
