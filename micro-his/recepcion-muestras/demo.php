<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

use Reception\Application\ReceiveSpecimen;
use Reception\Persistence\InMemoryReceptionRepository;
use Reception\Presentation\ReceptionController;

// Composition root MVC: fixture explicit, no authentication or external integration claimed.
$repository = new InMemoryReceptionRepository(['H1' => ['I1' => [
    'status' => 'pending', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo',
    'type' => 'blood', 'container' => 'EDTA', 'min_volume' => 3,
    'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25,
]]]);
$controller = new ReceptionController(new ReceiveSpecimen($repository));
$response = $controller->store([
    'item_id' => 'I1', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo',
    'type' => 'blood', 'container' => 'EDTA', 'volume' => 3, 'temperature' => 20,
    'collected_at' => (new DateTimeImmutable())->format(DATE_ATOM),
], 'H1', 'DEMO');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
