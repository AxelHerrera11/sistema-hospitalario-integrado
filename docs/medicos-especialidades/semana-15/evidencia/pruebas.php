<?php
declare(strict_types=1);
// Pruebas de la Semana 15 (unitarias, caja blanca y caja negra). Uso: php pruebas.php <ruta a micro-his/medicos-especialidades>
// No modifica el modulo. Un caso con defecto conocido espera el comportamiento del contrato y, si falla, se informa como DEFECTO Dnn.
use MicroHis\Medicos\Application\{AssociateSpecialty, QueryAvailability, RegisterDoctor};
use MicroHis\Medicos\Domain\{DoctorRuleViolation, Period, SlotPlanner, SpecialtyAssignmentPolicy, SpecialtyLink};
use MicroHis\Medicos\Persistence\{ConfigScheduleProvider, InMemoryAppointmentReader, InMemoryDoctorRepository, InMemoryPermissionChecker};
use MicroHis\Medicos\Presentation\{ApiKernel, Request};

require $argv[1] . '/app.php';

$results = [];
function t(string $id, string $tipo, string $req, string $caso, callable $run, ?string $defect = null): void
{
    global $results;
    [$ok, $got] = $run();
    $estado = $ok ? ($defect === null ? 'PASA' : 'PASA (corregido ' . $defect . ')') : ($defect === null ? 'FALLA' : 'DEFECTO ' . $defect);
    $results[] = $estado;
    printf("%-4s %-8s %-13s %-14s %s | %s\n", $id, $tipo, $req, $estado, $caso, $got);
}
function code(callable $fn): string
{
    try {
        $fn();
        return 'OK';
    } catch (DoctorRuleViolation $e) {
        return $e->getMessage() . '/' . $e->status;
    } catch (Throwable $e) {
        return get_class($e);
    }
}
function eq(string $expected, string $got): array { return [$expected === $got, $got]; }
function repo(): InMemoryDoctorRepository
{
    return new InMemoryDoctorRepository(
        ['CARD' => ['sp-card', 'active'], 'NEURO' => ['sp-neuro', 'active'], 'PED' => ['sp-ped', 'inactive'], 'ORTO' => ['sp-orto', 'active']],
        ['doc-ana' => ['user' => 'u-a', 'license' => 'COL-9', 'status' => 'active', 'period' => new Period('2026-01-01')]],
        ['doc-ana' => [new SpecialtyLink('sp-neuro', true, new Period('2026-01-01'))]],
    );
}
function api(bool $rbacDown = false, array $busy = ['2026-10-19' => ['08:30']]): ApiKernel
{
    return medicos_api(repo(), new InMemoryAppointmentReader(['doc-ana' => $busy]), new ConfigScheduleProvider(['doc-ana' => [1 => [['08:00', '09:30']]]]),
        new InMemoryPermissionChecker(['admin1' => ['medicos.registrar', 'medicos.asociar_especialidad', 'medicos.ver_disponibilidad'], 'recepcion1' => ['medicos.ver_disponibilidad']], $rbacDown));
}
function call(ApiKernel $api, string $m, string $p, array $h = ['X-User-ID' => 'admin1'], array $body = [], array $q = [], bool $json = true): string
{
    $r = $api->handle(new Request($m, $p, $h, $q, $body), $json);
    $b = json_decode($r['body'], true);
    return $r['status'] . ' ' . ($b['code'] ?? '') . (isset($b['doctors']) ? ' ' . json_encode($b['doctors']) : '');
}
$alta = ['local_user_ref_id' => 'u-x', 'license_number' => 'COL-1', 'specialty_code' => 'CARD', 'valid_from' => '2026-01-01'];
$asoc = ['specialty_code' => 'CARD', 'is_primary' => false, 'valid_from' => '2026-02-01'];
$av = ['specialty' => 'NEURO', 'date' => '2026-10-19'];
$link = fn (string $s, bool $p, string $f, ?string $to = null) => new SpecialtyLink($s, $p, new Period($f, $to));
$policy = new SpecialtyAssignmentPolicy();
$assoc = fn (InMemoryDoctorRepository $r) => new AssociateSpecialty($r, $r, $policy);
$reg = fn (InMemoryDoctorRepository $r) => new RegisterDoctor($r, $r);
$query = fn (array $sched = [1 => [['08:00', '09:30']]]) => new QueryAvailability($r = repo(), $r, new ConfigScheduleProvider(['doc-ana' => $sched]), new InMemoryAppointmentReader(), new SlotPlanner());

// ---- Unitarias (dominio y reglas, sin HTTP ni base) ----
t('U01', 'unitaria', 'RF-MED-04', 'Period: hasta = desde se rechaza (limite)', fn () => eq('INVALID_PERIOD/422', code(fn () => new Period('2026-05-01', '2026-05-01'))));
t('U02', 'unitaria', 'RF-MED-04', 'Period: hasta = desde + 1 dia se acepta (limite)', fn () => eq('OK', code(fn () => new Period('2026-05-01', '2026-05-02'))));
t('U03', 'unitaria', 'RF-MED-04', 'Period: 30-feb y 1-1-1 con un digito se rechazan', fn () => eq('INVALID_PERIOD/422|INVALID_PERIOD/422', code(fn () => new Period('2026-02-30')) . '|' . code(fn () => new Period('2026-1-1'))));
t('U04', 'unitaria', 'RF-MED-04', 'overlaps: [01-01,02-01) y [02-01,...) no se solapan; con 01-31 si', function () { $a = new Period('2026-01-01', '2026-02-01'); $r = [$a->overlaps(new Period('2026-02-01')), $a->overlaps(new Period('2026-01-31'))]; return [$r === [false, true], json_encode($r)]; });
t('U05', 'unitaria', 'RF-MED-05', 'Politica: misma especialidad solapada -> 409', fn () => eq('SPECIALTY_ALREADY_ASSOCIATED/409', code(fn () => $policy->assertCanAssociate(new Period('2026-01-01'), [$link('sp-a', false, '2026-02-01')], $link('sp-a', false, '2026-03-01')))));
t('U06', 'unitaria', 'RF-MED-05', 'Politica: segunda principal solapada -> 409; no solapada -> OK', fn () => eq('PRIMARY_SPECIALTY_CONFLICT/409|OK', code(fn () => $policy->assertCanAssociate(new Period('2026-01-01'), [$link('sp-a', true, '2026-01-01', '2026-06-01')], $link('sp-b', true, '2026-05-01'))) . '|' . code(fn () => $policy->assertCanAssociate(new Period('2026-01-01'), [$link('sp-a', true, '2026-01-01', '2026-06-01')], $link('sp-b', true, '2026-06-01')))));
t('U07', 'unitaria', 'RF-MED-04', 'Politica: vigencia fuera del perfil -> 422', fn () => eq('PERIOD_OUTSIDE_PROFILE/422', code(fn () => $policy->assertCanAssociate(new Period('2026-03-01'), [], $link('sp-a', false, '2026-02-01')))));
t('U08', 'unitaria', 'RF-MED-06', 'SlotPlanner: 08:00-09:30 en bloques de 30 menos 08:30', function () { $r = (new SlotPlanner())->freeSlots([['08:00', '09:30']], ['08:30']); return [$r === ['08:00', '09:00'], json_encode($r)]; });
t('U09', 'unitaria', 'RF-MED-06', 'SlotPlanner: ventana menor a un bloque -> []; bloque 0 -> excepcion', function () { $p = new SlotPlanner(); $r = json_encode($p->freeSlots([['08:00', '08:20']], [])) . '|' . code(fn () => $p->freeSlots([['08:00', '09:00']], [], 0)); return [$r === '[]|InvalidArgumentException', $r]; });
t('U10', 'unitaria', 'RF-MED-06', 'SlotPlanner: cita a las 08:15 (fuera de rejilla) bloquea 08:00', function () { $r = (new SlotPlanner())->freeSlots([['08:00', '09:00']], ['08:15']); return [!in_array('08:00', $r, true), json_encode($r)]; }, 'D-02');
t('U11', 'unitaria', 'RF-MED-02', 'RegisterDoctor: la licencia " COL-9" no duplica a "COL-9"', fn () => eq('LICENSE_ALREADY_REGISTERED/409', code(fn () => $reg(repo())->execute('a1', 'u-n', ' COL-9', 'CARD', '2026-01-01'))), 'D-01');

// ---- Caja blanca: cada decision del flujo con casos que cubren sus ramas ----
$wb = ['PAYLOAD_INVALID/422' => ['u-x', '   '], 'SPECIALTY_INACTIVE/422' => ['u-x', 'COL-1', 'PED'], 'LICENSE_ALREADY_REGISTERED/409' => ['u-x', 'COL-9', 'CARD'], 'OK' => ['u-x', 'COL-1', 'CARD']];
foreach (array_values(array_keys($wb)) as $i => $esperado) {
    $a = $wb[$esperado];
    t('B0' . ($i + 1), 'blanca', 'RF-MED-01..03', 'RegisterDoctor rama ' . ($i + 1) . '/4', fn () => eq($esperado, code(fn () => $reg(repo())->execute('a1', $a[0], $a[1] ?? '', $a[2] ?? 'CARD', '2026-01-01'))));
}
$ramas = ['SPECIALTY_INACTIVE/422' => ['doc-ana', 'PED', false], 'DOCTOR_NOT_FOUND/404' => ['doc-x', 'CARD', false], 'PERIOD_OUTSIDE_PROFILE/422' => ['doc-ana', 'CARD', false, '2025-01-01'], 'OK' => ['doc-ana', 'CARD', false, '2026-02-01']];
$n = 5;
foreach ($ramas as $esperado => $a) {
    t('B0' . $n, 'blanca', 'RF-MED-03..05', 'AssociateSpecialty rama ' . ($n - 4) . '/4', fn () => eq($esperado, code(fn () => $assoc(repo())->execute('a1', $a[0], $a[1], $a[2], $a[3] ?? '2026-02-01'))));
    $n++;
}
$mcdc = [[true, true, 'PRIMARY_SPECIALTY_CONFLICT/409'], [true, false, 'OK'], [false, true, 'OK']];
foreach ($mcdc as $i => [$existente, $nueva, $esperado]) {
    t('B' . sprintf('%02d', 9 + $i), 'blanca', 'RF-MED-05', 'Condicion link.principal && nueva.principal = ' . json_encode([$existente, $nueva]), fn () => eq($esperado, code(fn () => $policy->assertCanAssociate(new Period('2026-01-01'), [$link('sp-a', $existente, '2026-01-01')], $link('sp-b', $nueva, '2026-02-01')))));
}
t('B12', 'blanca', 'RF-MED-06,07', 'QueryAvailability: fecha invalida, especialidad inactiva, sin medicos, sin horario, OK', function () use ($query) {
    $q = $query();
    $got = implode('|', [code(fn () => $q->execute('NEURO', '2026-13-01')), code(fn () => $q->execute('PED', '2026-10-19')), $q->execute('CARD', '2026-10-19')['code'], $query([])->execute('NEURO', '2026-10-19')['doctors'][0]['slots'] === [] ? 'vacio' : 'x', $q->execute('NEURO', '2026-10-19')['code']]);
    return eq('INVALID_DATE_RANGE/422|SPECIALTY_INACTIVE/422|NO_DOCTORS_FOR_SPECIALTY|vacio|OK', $got);
});

// ---- Caja negra: solo la interfaz HTTP del contrato (X-User-ID, cuerpo, respuesta) ----
t('N01', 'negra', 'RF-MED-01', 'Equivalencia valida: alta correcta', fn () => [str_starts_with(call(api(), 'POST', '/api/v1/doctors', body: $alta), '201'), 'alta de COL-1 -> 201']);
t('N02', 'negra', 'RF-MED-01..03', 'Equivalencia invalida: licencia duplicada / especialidad inactiva / inexistente / vacios', fn () => eq('409 LICENSE_ALREADY_REGISTERED|422 SPECIALTY_INACTIVE|422 SPECIALTY_INACTIVE|422 PAYLOAD_INVALID', implode('|', [call(api(), 'POST', '/api/v1/doctors', body: ['license_number' => 'COL-9'] + $alta), call(api(), 'POST', '/api/v1/doctors', body: ['specialty_code' => 'PED'] + $alta), call(api(), 'POST', '/api/v1/doctors', body: ['specialty_code' => 'XXX'] + $alta), call(api(), 'POST', '/api/v1/doctors', body: [])])));
t('N03', 'negra', 'RF-MED-04', 'Limites de vigencia en la asociacion: hasta = desde, hasta = desde + 1, fecha imposible', fn () => eq('422 INVALID_PERIOD|201 |422 INVALID_PERIOD', implode('|', array_map(fn ($b) => preg_replace('/\{.*/', '', call(api(), 'POST', '/api/v1/doctors/doc-ana/specialties', body: $b + $asoc)), [['valid_to' => '2026-02-01'], ['valid_to' => '2026-02-02'], ['valid_from' => '2026-02-30']]))));
t('N04', 'negra', 'RF-MED-05', 'Asociacion: repetida -> 409 y segunda principal -> 409', function () { $a = api(); call($a, 'POST', '/api/v1/doctors/doc-ana/specialties', body: $GLOBALS['asoc']); return eq('409 SPECIALTY_ALREADY_ASSOCIATED|409 PRIMARY_SPECIALTY_CONFLICT', call($a, 'POST', '/api/v1/doctors/doc-ana/specialties', body: $GLOBALS['asoc']) . '|' . call($a, 'POST', '/api/v1/doctors/doc-ana/specialties', body: ['specialty_code' => 'ORTO', 'is_primary' => true] + $GLOBALS['asoc'])); });
t('N05', 'negra', 'RNF-MED-01', 'Permisos: sin actor 401; sin permiso 403; con permiso 201', fn () => eq('401 UNAUTHENTICATED|403 FORBIDDEN|201 ', implode('|', [call(api(), 'POST', '/api/v1/doctors', [], $alta), call(api(), 'POST', '/api/v1/doctors', ['X-User-ID' => 'recepcion1'], $alta), preg_replace('/\{.*|doc-\d+/', '', call(api(), 'POST', '/api/v1/doctors', body: $alta))])));
t('N06', 'negra', 'RNF-MED-01', 'RBAC caido: las tres rutas fallan cerradas con 503', fn () => eq('503 AUTHORIZATION_UNAVAILABLE|503 AUTHORIZATION_UNAVAILABLE|503 AUTHORIZATION_UNAVAILABLE', implode('|', [call(api(true), 'POST', '/api/v1/doctors', body: $alta), call(api(true), 'POST', '/api/v1/doctors/doc-ana/specialties', body: $asoc), call(api(true), 'GET', '/api/v1/availability', q: $av)])));
t('N07', 'negra', 'RNF-MED-01', 'Tenant ajeno: X-Tenant-ID de otro hospital -> 401/403', fn () => [str_starts_with(call(api(), 'POST', '/api/v1/doctors', ['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'hospital-ajeno'], $alta), '40'), call(api(), 'POST', '/api/v1/doctors', ['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'hospital-ajeno'], $alta)], 'D-03');
t('N08', 'negra', 'RF-MED-06', 'Tipos incorrectos y JSON malformado -> 422 PAYLOAD_INVALID', fn () => eq('422 PAYLOAD_INVALID|422 PAYLOAD_INVALID|422 PAYLOAD_INVALID', implode('|', [call(api(), 'POST', '/api/v1/doctors', body: ['license_number' => 123] + $alta), call(api(), 'POST', '/api/v1/doctors/doc-ana/specialties', body: ['is_primary' => 'false'] + $asoc), call(api(), 'POST', '/api/v1/doctors', body: [], json: false)])));
t('N09', 'negra', 'RF-MED-06', 'Disponibilidad: lunes con cita 08:30 -> 08:00 y 09:00', fn () => [str_contains(call(api(), 'GET', '/api/v1/availability', q: $av), '"slots":["08:00","09:00"]'), call(api(), 'GET', '/api/v1/availability', q: $av)]);
t('N10', 'negra', 'RF-MED-07', 'Dia sin franjas y especialidad sin medicos: respuesta explicita', function () use ($av) { $r = call(api(), 'GET', '/api/v1/availability', q: ['date' => '2026-10-20'] + $av) . ' | ' . call(api(), 'GET', '/api/v1/availability', q: ['specialty' => 'CARD'] + $av); return [str_contains($r, '"slots":[]') && str_contains($r, 'NO_DOCTORS_FOR_SPECIALTY'), $r]; });
t('N11', 'negra', 'RF-MED-06', 'Disponibilidad: fecha imposible -> 422 INVALID_DATE_RANGE; sin specialty -> 422 PAYLOAD_INVALID', fn () => eq('422 INVALID_DATE_RANGE|422 PAYLOAD_INVALID', call(api(), 'GET', '/api/v1/availability', q: ['date' => '2026-02-30'] + $av) . '|' . call(api(), 'GET', '/api/v1/availability', q: ['date' => '2026-10-19'])), 'D-04');
t('N12', 'negra', 'RNF-MED-01', 'Ruta sin permiso declarado no queda abierta (GET /specialties)', fn () => [!in_array(ApiKernel::permissionFor('GET', '/specialties'), [null], true), (string) var_export(ApiKernel::permissionFor('GET', '/specialties'), true)], 'D-05');
t('N13', 'negra', 'RF-MED-06', 'Trazabilidad: toda respuesta, tambien 4xx, lleva X-Trace-Id', function () { $r = api()->handle(new Request('POST', '/api/v1/doctors', ['X-User-ID' => 'admin1'], [], [])); return [isset($r['headers']['X-Trace-Id']) && isset(json_decode($r['body'], true)['trace_id']), (string) $r['status']]; });

$total = count($results);
$defects = count(array_filter($results, fn ($r) => str_starts_with($r, 'DEFECTO')));
$fails = count(array_filter($results, fn ($r) => $r === 'FALLA'));
printf("\nCasos: %d | pasan: %d | defectos conocidos: %d | fallas inesperadas: %d\n", $total, $total - $defects - $fails, $defects, $fails);
exit($fails > 0 ? 1 : 0);
