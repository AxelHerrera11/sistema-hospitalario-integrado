<?php
declare(strict_types=1);
require __DIR__.'/../bootstrap.php';
use Reception\Application\ReceiveSpecimen;
use Reception\Persistence\PdoReceptionRepository;

$database = getenv('RECEPTION_TEST_DATABASE') ?: (getenv('HOSPITAL_DB_DATABASE') ?: 'his_hospital_test');
if (!str_ends_with($database, '_test')) throw new RuntimeException('Use una base *_test');
$dsn = 'pgsql:host=127.0.0.1;port='.(getenv('RECEPTION_TEST_PORT') ?: (getenv('HOSPITAL_DB_PORT') ?: '55433')).';dbname='.$database;
$pdo = new PDO($dsn, getenv('RECEPTION_TEST_USERNAME') ?: 'postgres', getenv('RECEPTION_TEST_PASSWORD') ?: 'postgres',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$schema = 'rx_micro_'.bin2hex(random_bytes(6));
$pdo->exec('CREATE SCHEMA '.$schema);
$pdo->exec('SET search_path TO '.$schema);
$checks = 0;
function verify(bool $condition, string $name): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL '.$name);
    echo 'PASS '.++$checks.' '.$name.PHP_EOL;
}
try {
    $pdo->exec(file_get_contents(__DIR__.'/../schema.sql'));
    $statement = $pdo->prepare("INSERT INTO reception_order_items VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach (['I1', 'I2', 'I3'] as $id) $statement->execute(['H1', $id, 'P001', 'Ana Demo', 'blood', 'EDTA', 3, 60, 15, 25, 'pending']);
    $input = ['item_id' => 'I1', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo', 'type' => 'blood',
        'container' => 'EDTA', 'volume' => 3, 'temperature' => 20, 'collected_at' => '2026-10-20T10:00:00-06:00'];
    $repository = new PdoReceptionRepository($pdo);
    $service = new ReceiveSpecimen($repository);
    $now = new DateTimeImmutable('2026-10-20T10:10:00-06:00');
    $accepted = $service->execute('H1', 'T1', $input, $now);
    verify($accepted['status'] === 'accepted', 'Micro PDO: recepción aceptada');
    verify($repository->find('H1', $accepted['code'])['id'] === $accepted['id'], 'Micro PDO: lectura persistida');
    verify($repository->find('H2', $accepted['code']) === null, 'Micro PDO: aislamiento de lectura');
    $input['item_id'] = 'I2'; $input['volume'] = 1;
    $rejected = $service->execute('H1', 'T1', $input, $now);
    verify($rejected['status'] === 'rejected' && $rejected['code'] === null, 'Micro PDO: rechazo de dominio');
    verify((int) $pdo->query('SELECT count(*) FROM reception_outbox')->fetchColumn() === 1, 'Micro PDO: aviso durable');
    $pdo->exec('DROP TABLE reception_outbox');
    $input['item_id'] = 'I3';
    try { $service->execute('H1', 'T1', $input, $now); throw new LogicException('Fallo ignorado'); }
    catch (PDOException) {
        verify((int) $pdo->query("SELECT count(*) FROM reception_specimens WHERE item_id = 'I3'")->fetchColumn() === 0,
            'Micro PDO: rollback de muestra al fallar persistencia');
        verify($pdo->query("SELECT status FROM reception_order_items WHERE item_id = 'I3'")->fetchColumn() === 'pending',
            'Micro PDO: rollback de estado de orden');
    }
    echo "OK $checks comprobaciones PostgreSQL del micro.".PHP_EOL;
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec('DROP SCHEMA '.$schema.' CASCADE');
}
