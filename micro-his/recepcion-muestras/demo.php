<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use Reception\Application\ReceiveSpecimen;
use Reception\Domain\Uuid;
use Reception\Persistence\InMemoryReceptionRepository;
use Reception\Persistence\PdoReceptionRepository;
use Reception\Presentation\JsonView;
use Reception\Presentation\ReceptionController;

$item = Uuid::generate();
$order = ['status' => 'pending', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo', 'type' => 'blood',
    'container' => 'EDTA', 'min_volume' => 3, 'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25,
    'local_patient_id' => Uuid::generate(), 'care_episode_id' => Uuid::generate()];
$orders = new InMemoryReceptionRepository(['H1' => [$item => $order]]);
$repository = $orders;
$pdo = null;
$schema = null;
try {
    if (in_array('--pdo', $argv, true)) {
        $database = getenv('RECEPTION_TEST_DATABASE') ?: 'his_reception_test';
        if ($database !== 'his_reception_test') {
            throw new RuntimeException('Demo exclusiva para his_reception_test');
        }
        $pdo = new PDO('pgsql:host=127.0.0.1;port='.(getenv('RECEPTION_TEST_PORT') ?: '55433').';dbname='.$database,
            getenv('RECEPTION_TEST_USERNAME') ?: 'postgres', getenv('RECEPTION_TEST_PASSWORD') ?: 'postgres', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $created = 'rec_demo_'.bin2hex(random_bytes(6));
        $pdo->exec('CREATE SCHEMA '.$created);
        $schema = $created;
        $pdo->exec('SET search_path TO '.$schema);
        $pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
        $repository = new PdoReceptionRepository($pdo);
    }
    $controller = new ReceptionController(new ReceiveSpecimen($repository, $orders));
    $response = $controller->store(['item_id' => $item, 'request_key' => Uuid::generate(), 'patient_code' => 'P001',
        'patient_name' => 'Ana Demo', 'type' => 'blood', 'container' => 'EDTA', 'volume' => 3, 'temperature' => 20,
        'collected_at' => (new DateTimeImmutable)->format(DATE_ATOM)], 'H1', Uuid::generate());
    if ($response['status'] !== 201) {
        throw new RuntimeException('Demo no aceptada: '.json_encode($response));
    }
    echo (new JsonView)->render($response);
} finally {
    if ($pdo && $schema) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pdo->exec('DROP SCHEMA '.$schema.' CASCADE');
    }
}
