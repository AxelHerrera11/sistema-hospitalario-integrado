<?php

declare(strict_types=1);

use MicroHis\Occupancy\Application\ConsultOccupancy;
use MicroHis\Occupancy\Domain\OccupancyCalculator;
use MicroHis\Occupancy\Persistence\PdoBedSnapshotReader;
use MicroHis\Occupancy\Presentation\OccupancyController;

require __DIR__ . '/../bootstrap.php';

try {
    $pdo = new PDO(
        getenv('MICROHIS_PG_DSN') ?: 'pgsql:host=127.0.0.1;dbname=his_hospital',
        getenv('MICROHIS_PG_USER') ?: null,
        getenv('MICROHIS_PG_PASSWORD') ?: null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false],
    );
    $controller = new OccupancyController(new ConsultOccupancy(new PdoBedSnapshotReader($pdo), new OccupancyCalculator()));
    $response = $controller->summary();
} catch (PDOException) {
    $response = ['status' => 503, 'body' => ['error' => 'OCCUPANCY_UNAVAILABLE']];
}
http_response_code($response['status']);
header('Content-Type: application/json');
echo json_encode($response['body'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
