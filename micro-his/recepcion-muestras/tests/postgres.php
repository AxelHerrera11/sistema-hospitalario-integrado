<?php

declare(strict_types=1);

require __DIR__.'/../bootstrap.php';

use Reception\Application\ReceiveSpecimen;
use Reception\Domain\ReceptionError;
use Reception\Domain\SpecimenCode;
use Reception\Domain\Uuid;
use Reception\Persistence\InMemoryReceptionRepository;
use Reception\Persistence\PdoReceptionRepository;

$database = getenv('RECEPTION_TEST_DATABASE') ?: 'his_reception_test';
if ($database !== 'his_reception_test') {
    throw new RuntimeException('Use exclusivamente his_reception_test');
}
$pdo = new PDO('pgsql:host=127.0.0.1;port='.(getenv('RECEPTION_TEST_PORT') ?: '15438').';dbname='.$database,
    getenv('RECEPTION_TEST_USERNAME') ?: 'postgres', getenv('RECEPTION_TEST_PASSWORD') ?: 'postgres', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$schema = 'rec_test_'.bin2hex(random_bytes(6));
$pdo->exec('CREATE SCHEMA '.$schema);
$pdo->exec('SET search_path TO '.$schema);
$count = 0;
function verify(bool $condition, string $message): void
{
    global $count;
    if (! $condition) {
        throw new RuntimeException('FAIL '.$message);
    }
    echo 'PASS '.++$count.' '.$message.PHP_EOL;
}
try {
    $pdo->exec(file_get_contents(__DIR__.'/../schema.sql'));
    $id = Uuid::generate();
    $order = ['status' => 'pending', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo', 'type' => 'blood',
        'container' => 'EDTA', 'min_volume' => 3, 'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25,
        'local_patient_id' => Uuid::generate(), 'care_episode_id' => Uuid::generate()];
    $orders = new InMemoryReceptionRepository(['H1' => [$id => $order]]);
    $input = ['item_id' => $id, 'request_key' => Uuid::generate(), 'patient_code' => 'P001', 'patient_name' => 'Ana Demo',
        'type' => 'blood', 'container' => 'EDTA', 'volume' => 3, 'temperature' => 20, 'collected_at' => '2026-10-23T11:30:00+00:00'];
    $actor = Uuid::generate();
    $now = new DateTimeImmutable('2026-10-23T12:00:00Z');
    $repository = new PdoReceptionRepository($pdo);
    $service = new ReceiveSpecimen($repository, $orders);
    $accepted = $service->execute('H1', $actor, $input, $now);
    verify($accepted['status'] === 'accepted', 'PDO recepción aceptada');
    verify($repository->find('H1', $accepted['code']) === $accepted, 'PDO lectura y tipos de la respuesta estables');
    verify($service->execute('H1', $actor, $input, $now) === $accepted, 'Replay devuelve respuesta persistida');
    verify((int) $pdo->query('SELECT count(*) FROM outbox_events')->fetchColumn() === 1, 'Replay no duplica el evento UUID');
    verify($repository->find('H1', "' OR 1=1 --") === null, 'Consulta preparada no interpreta SQL en el código');
    $types = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name='specimens'")->fetchAll(PDO::FETCH_KEY_PAIR);
    verify($types['specimen_id'] === 'uuid' && $types['lab_order_item_id'] === 'uuid' && $types['volume'] === 'numeric' && $types['temperature'] === 'numeric', 'UUID y numeric reales');
    verify(! isset($types['tenant_id']), 'Aislamiento por conexión, sin columna tenant_id');
    try {
        $service->execute('H1', $actor, array_replace($input, ['volume' => 4]), $now);
        throw new LogicException('Conflicto omitido');
    } catch (ReceptionError $error) {
        verify($error->error === 'REQUEST_CONFLICT' && $error->getCode() === 409, 'Clave repetida con datos distintos da 409');
    }
    $input['request_key'] = Uuid::generate();
    $input['volume'] = 1;
    $rejected = $service->execute('H1', $actor, $input, $now);
    verify($rejected['status'] === 'rejected' && $rejected['reason'] === 'volumen_insuficiente', 'Rechazo de dominio persistido');
    verify((int) $pdo->query('SELECT count(*) FROM outbox_events')->fetchColumn() === 2, 'Evento también para el rechazo');
    $input['item_id'] = Uuid::generate();
    $input['request_key'] = Uuid::generate();
    $input['volume'] = 3;
    $orders->orders['H1'][$input['item_id']] = $order;
    $attempts = 0;
    $collision = new ReceiveSpecimen($repository, $orders, function () use (&$attempts, $accepted): string {
        return ++$attempts === 1 ? $accepted['code'] : SpecimenCode::generate();
    });
    $saved = $collision->execute('H1', $actor, $input, $now);
    verify($attempts === 2 && $saved['code'] !== $accepted['code'], 'Colisión reintenta y termina en el primer éxito');
    $before = (int) $pdo->query('SELECT count(*) FROM specimens')->fetchColumn();
    $pdo->exec('DROP TABLE outbox_events');
    $input['item_id'] = Uuid::generate();
    $input['request_key'] = Uuid::generate();
    $orders->orders['H1'][$input['item_id']] = $order;
    try {
        $service->execute('H1', $actor, $input, $now);
        throw new LogicException('Fallo de outbox ignorado');
    } catch (PDOException) {
        verify((int) $pdo->query('SELECT count(*) FROM specimens')->fetchColumn() === $before, 'Fallo de evento revierte specimen');
        verify(! $pdo->inTransaction(), 'Rollback cierra la transacción');
    }
    echo "OK $count comprobaciones PostgreSQL reales del micro.".PHP_EOL;
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('DROP SCHEMA '.$schema.' CASCADE');
}
