<?php
declare(strict_types=1);
require __DIR__.'/../bootstrap.php';

use Reception\Application\ReceiveSpecimen;
use Reception\Domain\SpecimenCode;
use Reception\Persistence\InMemoryReceptionRepository;

$count = 0;
function check(bool $condition, string $name): void {
    global $count;
    if (!$condition) throw new RuntimeException('FAIL '.$name);
    echo 'PASS '.++$count.' '.$name.PHP_EOL;
}
function fixture(): array {
    $requirements = ['status' => 'pending', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo',
        'type' => 'blood', 'container' => 'EDTA', 'min_volume' => 3,
        'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25];
    $repository = new InMemoryReceptionRepository(['H1' => ['I1' => $requirements]]);
    $input = ['item_id' => 'I1', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo',
        'type' => 'blood', 'container' => 'EDTA', 'volume' => 3, 'temperature' => 15,
        'collected_at' => '2026-10-20T10:00:00-06:00'];
    return [$repository, new ReceiveSpecimen($repository), $input, new DateTimeImmutable('2026-10-20T11:00:00-06:00')];
}
[$repo, $service, $input, $now] = fixture();
$sample = $service->execute('H1', 'T1', $input, $now);
check($sample['status'] === 'accepted' && SpecimenCode::valid($sample['code']), 'RF01 aceptación y dígito');
check($repo->orders['H1']['I1']['status'] === 'received', 'RF02 actualización de orden');
check($repo->find('H1', $sample['code']) === $sample, 'RF03 reimpresión conserva código');
check($repo->find('H2', $sample['code']) === null, 'RNF01 aislamiento en consulta');
try { $service->execute('H1', 'T1', $input, $now); throw new RuntimeException('Duplicado permitido'); }
catch (DomainException $e) { check($e->getCode() === 409, 'RF04 doble aceptación bloqueada'); }

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
    $sample = $service->execute('H1', 'T1', $input, $now);
    check($sample['status'] === 'rejected' && $sample['reason'] === $reason && $sample['code'] === null,
        'RF05 rechazo '.$field.' '.(string) $value);
    check(count($repo->outbox) === 1 && $repo->orders['H1']['I1']['status'] === 'pending_new_sample', 'RF06 outbox y nueva toma');
}
foreach (['urine', 'swab'] as $type) {
    [$repo, $service, $input, $now] = fixture();
    $repo->orders['H1']['I1']['type'] = $input['type'] = $type;
    check($service->execute('H1', 'T1', $input, $now)['status'] === 'accepted', 'LSP '.$type);
}
foreach (['H2', 'missing'] as $tenant) {
    [$repo, $service, $input, $now] = fixture();
    try { $service->execute($tenant, 'T1', $input, $now); throw new RuntimeException('Orden ajena permitida'); }
    catch (DomainException $e) { check($e->getCode() === 404 && !$repo->samples, 'RNF01 orden ajena '.$tenant); }
}
[$repo, $service, $input, $now] = fixture();
$repo->orders['H1']['I1']['status'] = 'cancelled';
try { $service->execute('H1', 'T1', $input, $now); throw new RuntimeException('Cancelada permitida'); }
catch (DomainException $e) { check($e->getCode() === 409, 'RF04 orden cancelada'); }
[$repo, $service, $input, $now] = fixture();
$repo->fail = true;
try { $service->execute('H1', 'T1', $input, $now); throw new LogicException('Fallo ignorado'); }
catch (RuntimeException $e) {
    check($e->getMessage() === 'Persistencia no disponible' && !$repo->samples
        && !$repo->outbox && $repo->orders['H1']['I1']['status'] === 'pending', 'RNF02 rollback por fallo');
}
check(!SpecimenCode::valid('INVALIDO'), 'RF03 código malformado');
$code = SpecimenCode::generate();
$bad = substr($code, 0, -1).(((int) substr($code, -1) + 1) % 10);
check(!SpecimenCode::valid($bad), 'RF03 dígito alterado');
$codes = [];
for ($i = 0; $i < 1000; $i++) $codes[] = SpecimenCode::generate();
check(count(array_unique($codes)) === 1000, 'RF03 1000 códigos secuenciales sin duplicados (no concurrencia)');
[$repo, $service] = fixture();
$controller = new \Reception\Presentation\ReceptionController($service);
check($controller->store([], 'H1', 'T1')['status'] === 422 && !$repo->samples, 'MVC payload inválido');
echo "OK $count comprobaciones; sin medición de cobertura ni integraciones externas.".PHP_EOL;
