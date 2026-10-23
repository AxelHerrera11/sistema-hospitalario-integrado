<?php

declare(strict_types=1);
require __DIR__.'/../bootstrap.php';

use Reception\Application\ReceiveSpecimen;
use Reception\Domain\SpecimenCode;
use Reception\Domain\Uuid;
use Reception\Persistence\InMemoryReceptionRepository;
use Reception\Presentation\ReceptionController;

$count = 0;
function check(bool $condition, string $name): void
{
    global $count;
    if (! $condition) {
        throw new RuntimeException('FAIL '.$name);
    }
    echo 'PASS '.++$count.' '.$name.PHP_EOL;
}
function fixture(): array
{
    $requirements = ['status' => 'pending', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo',
        'type' => 'blood', 'container' => 'EDTA', 'min_volume' => 3,
        'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25,
        'local_patient_id' => Uuid::generate(), 'care_episode_id' => Uuid::generate()];
    $repository = new InMemoryReceptionRepository(['H1' => ['11111111-1111-4111-8111-111111111111' => $requirements]]);
    $input = ['item_id' => '11111111-1111-4111-8111-111111111111', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo',
        'type' => 'blood', 'container' => 'EDTA', 'volume' => 3, 'temperature' => 15,
        'collected_at' => '2026-10-20T10:00:00-06:00', 'request_key' => Uuid::generate()];

    return [$repository, new ReceiveSpecimen($repository, $repository), $input, new DateTimeImmutable('2026-10-20T11:00:00-06:00')];
}
[$repo, $service, $input, $now] = fixture();
$sample = $service->execute('H1', '22222222-2222-4222-8222-222222222222', $input, $now);
check($sample['status'] === 'accepted' && SpecimenCode::valid($sample['code']), 'RF01 aceptación y dígito');
check($repo->orders['H1']['11111111-1111-4111-8111-111111111111']['status'] === 'pending', 'RF02 puerto de órdenes de solo lectura');
check($repo->find('H1', $sample['code']) === $sample, 'RF03 reimpresión conserva código');
check($repo->find('H2', $sample['code']) === null, 'RNF01 aislamiento en consulta');
check($service->execute('H1', '22222222-2222-4222-8222-222222222222', $input, $now) === $sample, 'RF07 replay idempotente');
$input['request_key'] = Uuid::generate();
try {
    $service->execute('H1', '22222222-2222-4222-8222-222222222222', $input, $now);
    throw new RuntimeException('Duplicado permitido');
} catch (DomainException $e) {
    check($e->getCode() === 409, 'RF04 doble aceptación bloqueada');
}

$cases = [
    ['patient_code', 'otro', 'identificacion_discordante'],
    ['patient_name', 'Otra persona', 'identificacion_discordante'],
    ['type', 'urine', 'tipo_incorrecto'],
    ['container', 'frasco', 'contenedor_incorrecto'],
    ['volume', 2.9, 'volumen_insuficiente'],
    ['temperature', 14.9, 'temperatura_inadecuada'],
    ['temperature', 25.1, 'temperatura_inadecuada'],
    ['collected_at', '2026-10-20T09:59:59-06:00', 'fuera_de_estabilidad'],
    ['collected_at', '2026-10-20T11:00:01-06:00', 'fuera_de_estabilidad'],
    ['hemolizada', true, 'hemolizada'], ['coagulada', true, 'coagulada'], ['derramada', true, 'derramada'],
];
foreach ($cases as [$field, $value, $reason]) {
    [$repo, $service, $input, $now] = fixture();
    $input[$field] = $value;
    $sample = $service->execute('H1', '22222222-2222-4222-8222-222222222222', $input, $now);
    check($sample['status'] === 'rejected' && $sample['reason'] === $reason && $sample['code'] === null,
        'RF05 rechazo '.$field.' '.(string) $value);
    check(count($repo->outbox) === 1 && $repo->orders['H1']['11111111-1111-4111-8111-111111111111']['status'] === 'pending', 'RF06 outbox y orden externa sin modificar');
}
foreach (['urine', 'swab'] as $type) {
    [$repo, $service, $input, $now] = fixture();
    $repo->orders['H1']['11111111-1111-4111-8111-111111111111']['type'] = $input['type'] = $type;
    check($service->execute('H1', '22222222-2222-4222-8222-222222222222', $input, $now)['status'] === 'accepted', 'LSP '.$type);
}
foreach (['H2', 'missing'] as $tenant) {
    [$repo, $service, $input, $now] = fixture();
    try {
        $service->execute($tenant, '22222222-2222-4222-8222-222222222222', $input, $now);
        throw new RuntimeException('Orden ajena permitida');
    } catch (DomainException $e) {
        check($e->getCode() === 404 && ! $repo->samples, 'RNF01 orden ajena '.$tenant);
    }
}
[$repo, $service, $input, $now] = fixture();
$repo->orders['H1']['11111111-1111-4111-8111-111111111111']['status'] = 'cancelled';
try {
    $service->execute('H1', '22222222-2222-4222-8222-222222222222', $input, $now);
    throw new RuntimeException('Cancelada permitida');
} catch (DomainException $e) {
    check($e->getCode() === 409, 'RF04 orden cancelada');
}
[$repo, $service, $input, $now] = fixture();
$repo->fail = true;
try {
    $service->execute('H1', '22222222-2222-4222-8222-222222222222', $input, $now);
    throw new LogicException('Fallo ignorado');
} catch (RuntimeException $e) {
    check($e->getMessage() === 'Persistencia no disponible' && ! $repo->samples
        && ! $repo->outbox && $repo->orders['H1']['11111111-1111-4111-8111-111111111111']['status'] === 'pending', 'RNF02 rollback por fallo');
}
check(! SpecimenCode::valid('INVALIDO'), 'RF03 código malformado');
$code = SpecimenCode::generate();
$bad = substr($code, 0, -1).(((int) substr($code, -1) + 1) % 10);
check(! SpecimenCode::valid($bad), 'RF03 dígito alterado');
[$repo, $service] = fixture();
$controller = new ReceptionController($service);
check($controller->store([], 'H1', '22222222-2222-4222-8222-222222222222')['status'] === 422 && ! $repo->samples, 'MVC payload inválido');
echo "OK $count comprobaciones; sin medición de cobertura ni integraciones externas.".PHP_EOL;
