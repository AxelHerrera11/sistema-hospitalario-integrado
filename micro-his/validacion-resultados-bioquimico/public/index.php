<?php

declare(strict_types=1);

use MicroHis\ValidacionResultados\Application\RejectReviewedResult;
use MicroHis\ValidacionResultados\Application\ValidateReviewedResult;
use MicroHis\ValidacionResultados\Domain\ReviewableLabResult;
use MicroHis\ValidacionResultados\Persistence\ArrayReviewAuditLogger;
use MicroHis\ValidacionResultados\Persistence\InMemoryReviewResultRepository;
use MicroHis\ValidacionResultados\Presentation\HttpReviewController;

require_once __DIR__ . '/../bootstrap.php';

$repository = new InMemoryReviewResultRepository([
    new ReviewableLabResult('rev-001', 'hospital-demo', 'PAC-001', 'GLU', '95', 'mg/dL', 'mg/dL'),
]);
$audit = new ArrayReviewAuditLogger();
$controller = new HttpReviewController(
    new ValidateReviewedResult($repository, $audit),
    new RejectReviewedResult($repository, $audit),
);

$action = $_POST['action'] ?? 'validate';

header('Content-Type: application/json');
echo json_encode(
    $action === 'reject' ? $controller->reject($_POST) : $controller->validate($_POST),
    JSON_PRETTY_PRINT
);
