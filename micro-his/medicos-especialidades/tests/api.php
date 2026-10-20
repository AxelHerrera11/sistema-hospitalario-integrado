<?php

declare(strict_types=1);

use MicroHis\Medicos\Client\DoctorApiClient;
use MicroHis\Medicos\Client\DoctorApiError;
use MicroHis\Medicos\Client\DoctorApiUnavailable;
use MicroHis\Medicos\Client\HttpTransport;
use MicroHis\Medicos\Client\KernelTransport;
use MicroHis\Medicos\Client\StreamTransport;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyLink;
use MicroHis\Medicos\Persistence\ConfigScheduleProvider;
use MicroHis\Medicos\Persistence\InMemoryAppointmentReader;
use MicroHis\Medicos\Persistence\InMemoryDoctorRepository;
use MicroHis\Medicos\Persistence\InMemoryPermissionChecker;
use MicroHis\Medicos\Presentation\ApiKernel;
use MicroHis\Medicos\Presentation\Request;

require __DIR__ . '/../app.php';

/** Servidor API v1 con InMemory: Ana atiende NEURO los lunes de 08:00 a 09:30 y tiene la cita de las 08:30 confirmada. */
function api(bool $rbacDown = false, array &$log = []): ApiKernel
{
    $doctors = new InMemoryDoctorRepository(
        ['CARD' => ['sp-card', 'active'], 'NEURO' => ['sp-neuro', 'active']],
        ['doc-ana' => ['user' => 'u-a', 'license' => 'COL-9', 'status' => 'active', 'period' => new Period('2026-01-01')]],
        ['doc-ana' => [new SpecialtyLink('sp-neuro', true, new Period('2026-01-01'))]],
    );
    $permissions = new InMemoryPermissionChecker([
        'admin1' => ['medicos.registrar', 'medicos.especialidades.asociar', 'medicos.disponibilidad.ver'],
        'recepcion1' => ['medicos.disponibilidad.ver'],
    ], $rbacDown);
    $sequence = 0;
    return medicos_api($doctors, new InMemoryAppointmentReader(['doc-ana' => ['2026-10-19' => ['08:30']]]), new ConfigScheduleProvider(['doc-ana' => [1 => [['08:00', '09:30']]]]),
        $permissions, static function () use (&$sequence): string { return 'trace-' . ++$sequence; }, static function (array $entry) use (&$log): void { $log[] = $entry; });
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

/** @return array{0: int, 1: array<string, mixed>, 2: array<string, string>} */
function call(ApiKernel $api, string $method, string $path, array $headers = ['X-User-ID' => 'admin1'], array $body = [], array $query = [], bool $validJson = true): array
{
    $r = $api->handle(new Request($method, $path, $headers, $query, $body), $validJson);
    return [$r['status'], json_decode($r['body'], true), $r['headers']];
}

$alta = ['local_user_ref_id' => 'u-ana', 'license_number' => 'COL-1', 'specialty_code' => 'CARD', 'valid_from' => '2026-01-01'];
$log = [];
$server = api(false, $log);

[$status, $body, $headers] = call($server, 'POST', '/api/v1/doctors', body: $alta);
check('servidor: POST /api/v1/doctors con permiso -> 201 con trace_id en cuerpo y encabezado', $status === 201 && $body['trace_id'] === 'trace-1' && $headers['X-Trace-Id'] === 'trace-1');
check('servidor: sin X-User-ID -> 401 UNAUTHENTICATED', call($server, 'POST', '/api/v1/doctors', [], $alta)[1]['code'] === 'UNAUTHENTICATED');
[$status, $body] = call($server, 'POST', '/api/v1/doctors', ['X-User-ID' => 'recepcion1'], $alta);
check('servidor: recepción sin medicos.registrar -> 403 FORBIDDEN con el permiso faltante', $status === 403 && $body['permission'] === 'medicos.registrar');
check('servidor: recepción con medicos.disponibilidad.ver -> 200', call($server, 'GET', '/api/v1/availability', ['X-User-ID' => 'recepcion1'], [], ['specialty' => 'NEURO', 'date' => '2026-10-19'])[0] === 200);
check('servidor: ruta sin /api/v1 -> 404 ROUTE_NOT_FOUND', call($server, 'POST', '/doctors', body: $alta)[1]['code'] === 'ROUTE_NOT_FOUND');
check('servidor: JSON malformado -> 422 PAYLOAD_INVALID con trace_id', call($server, 'POST', '/api/v1/doctors', validJson: false)[1] === ['code' => 'PAYLOAD_INVALID', 'trace_id' => 'trace-6']);
check('servidor: bitácora de acceso con trace_id, método, ruta y estado, sin el cuerpo', count($log) === 6 && $log[2] === ['trace_id' => 'trace-3', 'method' => 'POST', 'path' => '/api/v1/doctors', 'status' => 403]);
[$status, $body] = call(api(true), 'GET', '/api/v1/availability', query: ['specialty' => 'NEURO', 'date' => '2026-10-19']);
check('servidor: RBAC caído -> 503 AUTHORIZATION_UNAVAILABLE (falla cerrada, no se consulta)', $status === 503 && $body['code'] === 'AUTHORIZATION_UNAVAILABLE' && !isset($body['doctors']));

$client = new DoctorApiClient(new KernelTransport(api()), 'admin1');
$id = $client->registerDoctor('u-ana', 'COL-1', 'CARD', '2026-01-01');
check('cliente: registra y asocia una especialidad secundaria', $client->associateSpecialty($id, 'NEURO', false, '2026-02-01') === ['doctor_profile_id' => $id, 'specialty_id' => 'sp-neuro', 'is_primary' => false]);
check('cliente: disponibilidad del lunes -> Ana libre 08:00 y 09:00', array_column($client->availability('NEURO', '2026-10-19')['doctors'], 'slots', 'doctor_id')['doc-ana'] === ['08:00', '09:00']);
try {
    $client->registerDoctor('u-ana', 'COL-1', 'CARD', '2026-01-01');
    check('cliente: error del servidor -> DoctorApiError', false);
} catch (DoctorApiError $e) {
    check('cliente: licencia repetida -> DoctorApiError 409 con código y trace_id del servidor', $e->status === 409 && $e->errorCode === 'LICENSE_ALREADY_REGISTERED' && $e->traceId !== '');
}

/** Transporte que falla las primeras N llamadas: prueba el reintento único y que las altas no se reintentan. */
$flaky = new class (new KernelTransport(api())) implements HttpTransport {
    public int $calls = 0;
    public int $failures = 1;
    public function __construct(private readonly HttpTransport $inner)
    {
    }
    public function send(string $method, string $path, string $actorId, array $query = [], ?array $body = null): array
    {
        ++$this->calls;
        return $this->calls <= $this->failures ? throw new DoctorApiUnavailable('caído') : $this->inner->send($method, $path, $actorId, $query, $body);
    }
};
$client = new DoctorApiClient($flaky, 'admin1');
check('cliente: la consulta se reintenta una vez si el servidor no responde', $client->availability('NEURO', '2026-10-19')['code'] === 'OK' && $flaky->calls === 2);
foreach ([['consulta', 2, fn () => $client->availability('NEURO', '2026-10-19')], ['alta', 1, fn () => $client->registerDoctor('u-x', 'COL-5', 'CARD', '2026-01-01')]] as [$what, $expected, $action]) {
    [$flaky->calls, $flaky->failures] = [0, 5];
    try {
        $action();
        check("cliente: $what sin servidor -> DoctorApiUnavailable", false);
    } catch (DoctorApiUnavailable) {
        check("cliente: $what sin servidor -> DoctorApiUnavailable tras $expected intento(s), sin reintentos infinitos", $flaky->calls === $expected);
    }
}
try {
    (new StreamTransport('http://127.0.0.1:9', 0.5))->send('GET', '/api/v1/availability', 'admin1');
    check('cliente HTTP real: puerto cerrado -> DoctorApiUnavailable', false);
} catch (DoctorApiUnavailable) {
    check('cliente HTTP real: puerto cerrado -> DoctorApiUnavailable', true);
}
echo 'Todas las pruebas del API pasaron.' . PHP_EOL;
