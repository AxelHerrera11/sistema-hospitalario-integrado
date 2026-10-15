<?php

declare(strict_types=1);

use MicroHis\IngresoResultados\Application\CapturePendingResult;
use MicroHis\IngresoResultados\Domain\PendingLabResult;
use MicroHis\IngresoResultados\Persistence\ArrayAuditLogger;
use MicroHis\IngresoResultados\Persistence\InMemoryPendingResultRepository;
use MicroHis\IngresoResultados\Presentation\HttpResultController;

require_once __DIR__ . '/../bootstrap.php';

$repository = new InMemoryPendingResultRepository([
    new PendingLabResult('res-001', 'hospital-demo', 'PAC-001', 'GLU', 'mg/dL'),
]);
$audit = new ArrayAuditLogger();
$controller = new HttpResultController(new CapturePendingResult($repository, $audit));

header('Content-Type: application/json');
echo json_encode($controller->capture($_POST), JSON_PRETTY_PRINT);
