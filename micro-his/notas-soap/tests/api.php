<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Client\HttpTransport;
use MicroHis\NotasSoap\Client\KernelTransport;
use MicroHis\NotasSoap\Client\SoapNoteApiClient;
use MicroHis\NotasSoap\Client\SoapNoteApiError;
use MicroHis\NotasSoap\Client\SoapNoteApiUnavailable;
use MicroHis\NotasSoap\Client\StreamTransport;
use MicroHis\NotasSoap\Domain\Uuid;
use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;
use MicroHis\NotasSoap\Persistence\InMemoryPermissionChecker;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use MicroHis\NotasSoap\Persistence\PdoIcd10Catalog;
use MicroHis\NotasSoap\Presentation\ApiKernel;
use MicroHis\NotasSoap\Presentation\Request;

require __DIR__ . '/../app.php';

/** Roles ficticios: la Dra. López (médica tratante de ep1), el Dr. Pérez (médico sin asignación activa) y la enfermera Ruiz (solo lectura). */
const GRANTS = [
    'dra.lopez' => ['notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.ver'],
    'dr.perez' => ['notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.ver'],
    'enf.ruiz' => ['notas_soap.ver'],
];
const SOAP = ['subjective' => 'Tos de 3 días', 'objective' => 'T 38.4 °C', 'assessment' => 'Neumonía', 'plan' => 'Amoxicilina'];
const TRACE = '0b9e3c1a-6f2d-4c8e-9a71-5d3f2e1c0b4a';

/** Servidor sobre SQLite en memoria; el trace_id fijo es también el correlation_id de la auditoría, como en public/index.php. */
function server(bool $rbacDown = false, ?array &$log = null): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO care_assignments VALUES ('ep1', 'dra.lopez', 'active'), ('ep1', 'dr.perez', 'ended')");
    $pdo->exec("INSERT INTO icd10_codes VALUES ('J18.9', 'Neumonía, no especificada', 1)");
    $log = [];
    $api = notas_soap_api(new PdoClinicalNoteStore($pdo, new PdoAuditTrail($pdo, TRACE)), new PdoCareAssignmentReader($pdo), new PdoIcd10Catalog($pdo),
        new InMemoryPermissionChecker(GRANTS, $rbacDown), static fn (): string => TRACE, static function (array $entry) use (&$log): void {
            $log[] = $entry;
        });
    return [$api, $pdo];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

function send(ApiKernel $api, string $method, string $path, ?string $actor, ?array $body = []): array
{
    $response = $api->handle(new Request($method, $path, $actor === null ? [] : ['X-User-ID' => $actor], $body ?? []), $body !== null);
    return ['status' => $response['status'], 'trace' => $response['headers']['X-Trace-Id'] ?? '', 'body' => json_decode($response['body'], true)];
}

$status = static fn (array $r): string => $r['status'] . ' ' . ($r['body']['code'] ?? '');
$notes = '/api/v1/care-episodes/ep1/soap-notes';

[$api, $pdo] = server(log: $log);
$r = send($api, 'POST', $notes, 'dra.lopez', SOAP);
$nota = $r['body']['clinical_note_id'] ?? '';
check('servidor: POST /api/v1/.../soap-notes con permiso -> 201 con trace_id en cuerpo y encabezado', $r['status'] === 201 && $r['body']['trace_id'] === TRACE && $r['trace'] === TRACE && preg_match(Uuid::PATTERN, $nota) === 1);
check('observabilidad: la auditoría guarda el trace_id como correlation_id', $pdo->query('SELECT correlation_id FROM local_access_audits')->fetchAll(PDO::FETCH_COLUMN) === [TRACE]);
$r = send($api, 'POST', "/api/v1/soap-notes/$nota/diagnoses", 'dra.lopez', ['code' => 'J18.9', 'type' => 'primary']);
check('servidor: POST /api/v1/soap-notes/{id}/diagnoses con notas_soap.asociar_diagnostico -> 201', $r['status'] === 201 && $r['body']['code'] === 'J18.9');
check('seguridad: sin X-User-ID -> 401 UNAUTHENTICATED', $status(send($api, 'POST', $notes, null, SOAP)) === '401 UNAUTHENTICATED');
$r = send($api, 'POST', $notes, 'enf.ruiz', SOAP);
check('seguridad: enfermería sin notas_soap.crear -> 403 FORBIDDEN con el permiso faltante', $status($r) === '403 FORBIDDEN' && $r['body']['permission'] === 'notas_soap.crear');
check('seguridad: permiso RBAC sin asignación activa -> 403 NOT_TREATING_PHYSICIAN (dos niveles)', $status(send($api, 'POST', $notes, 'dr.perez', SOAP)) === '403 NOT_TREATING_PHYSICIAN');
check('contrato: ruta sin /api/v1 o sin permiso configurado -> 404 ROUTE_NOT_FOUND', $status(send($api, 'POST', '/care-episodes/ep1/soap-notes', 'dra.lopez', SOAP)) === '404 ROUTE_NOT_FOUND'
    && $status(send($api, 'DELETE', "/api/v1/soap-notes/$nota", 'dra.lopez')) === '404 ROUTE_NOT_FOUND');
$r = send($api, 'POST', $notes, 'dra.lopez', null);
check('contrato: JSON malformado -> 422 PAYLOAD_INVALID con trace_id', $status($r) === '422 PAYLOAD_INVALID' && $r['body']['trace_id'] === TRACE);
check('contrato: sección con tipo incorrecto -> 422 PAYLOAD_INVALID sin guardar', $status(send($api, 'POST', $notes, 'dra.lopez', ['plan' => 7] + SOAP)) === '422 PAYLOAD_INVALID'
    && (int) $pdo->query('SELECT COUNT(*) FROM clinical_notes')->fetchColumn() === 1);
check('observabilidad: bitácora de acceso con trace_id, actor, método, ruta y estado, sin el cuerpo clínico', array_keys($log[0]) === ['trace_id', 'actor', 'method', 'path', 'status']
    && $log[0]['status'] === 201 && !str_contains(json_encode($log, JSON_UNESCAPED_UNICODE), 'Tos de 3 días'));

[$api, $pdo] = server(rbacDown: true);
check('resiliencia: RBAC caído -> 503 AUTHORIZATION_UNAVAILABLE y no se guarda nada (falla cerrada)', $status(send($api, 'POST', $notes, 'dra.lopez', SOAP)) === '503 AUTHORIZATION_UNAVAILABLE'
    && (int) $pdo->query('SELECT COUNT(*) FROM clinical_notes')->fetchColumn() === 0);

$memory = notas_soap_api(new InMemoryClinicalNoteRepository(new InMemoryAuditTrail()), new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']]),
    new InMemoryIcd10Catalog(down: true), new InMemoryPermissionChecker(GRANTS));
$nota = send($memory, 'POST', $notes, 'dra.lopez', SOAP)['body']['clinical_note_id'];
$r = send($memory, 'POST', "/api/v1/soap-notes/$nota/diagnoses", 'dra.lopez', ['code' => 'J18.9', 'type' => 'primary']);
check('resiliencia: InMemory con catálogo CIE-10 caído -> 503 ICD10_UNAVAILABLE con trace_id UUID', $status($r) === '503 ICD10_UNAVAILABLE' && preg_match(Uuid::PATTERN, $r['body']['trace_id']) === 1);
[$api, $pdo] = server();
$pdo->exec("INSERT INTO care_assignments VALUES ('ep1', 'enf.ruiz', 'active')");
$client = new SoapNoteApiClient(new KernelTransport($api), 'dra.lopez');
$nota = $client->registerNote('ep1', ...array_values(SOAP));
$client->associateDiagnosis($nota, 'J18.9', 'primary');
$leida = (new SoapNoteApiClient(new KernelTransport($api), 'enf.ruiz'))->note($nota);
check('cliente: registra, asocia J18.9 y enfermería con notas_soap.ver lee secciones y diagnósticos', $leida['sections']['plan'] === 'Amoxicilina' && $leida['diagnoses'] === [['code' => 'J18.9', 'type' => 'primary']]);
check('seguridad: lectura con permiso pero sin asignación activa -> 404 NOTE_NOT_FOUND (no revela que la nota existe)', $status(send($api, 'GET', "/api/v1/soap-notes/$nota", 'dr.perez')) === '404 NOTE_NOT_FOUND');
try {
    $client->associateDiagnosis($nota, 'J18.9', 'secondary');
    check('cliente: código repetido -> SoapNoteApiError 409 con código y trace_id del servidor', false);
} catch (SoapNoteApiError $e) {
    check('cliente: código repetido -> SoapNoteApiError 409 con código y trace_id del servidor', $e->status === 409 && $e->errorCode === 'DIAGNOSIS_DUPLICATED' && $e->traceId === TRACE);
}

/** Transporte que falla las primeras $failures llamadas y después delega; cuenta los intentos. */
$flaky = static fn (int $failures, ?HttpTransport $inner = null): HttpTransport => new class ($failures, $inner) implements HttpTransport {
    public int $calls = 0;

    public function __construct(private int $failures, private readonly ?HttpTransport $inner)
    {
    }

    public function send(string $method, string $path, string $actorId, array $body = []): array
    {
        $this->calls++;
        return $this->failures-- > 0 || $this->inner === null ? throw new SoapNoteApiUnavailable() : $this->inner->send($method, $path, $actorId, $body);
    }
};
$once = $flaky(1, new KernelTransport($api));
check('resiliencia: la lectura se reintenta una vez si el servidor no responde', (new SoapNoteApiClient($once, 'enf.ruiz'))->note($nota)['status'] === 'draft' && $once->calls === 2);
foreach (['lectura' => [static fn (SoapNoteApiClient $c) => $c->note($nota), 2], 'registro' => [static fn (SoapNoteApiClient $c) => $c->registerNote('ep1', 'a', 'b', 'c', 'd'), 1]] as $op => [$call, $expected]) {
    $down = $flaky(PHP_INT_MAX);
    try {
        $call(new SoapNoteApiClient($down, 'dra.lopez'));
        check("resiliencia: $op sin servidor -> SoapNoteApiUnavailable", false);
    } catch (SoapNoteApiUnavailable) {
        check("resiliencia: $op sin servidor -> SoapNoteApiUnavailable tras {$down->calls} intento(s), sin reintentos infinitos", $down->calls === $expected);
    }
}
try {
    (new SoapNoteApiClient(new StreamTransport('http://127.0.0.1:1', 0.5), 'dra.lopez'))->note($nota);
    check('cliente HTTP real: puerto cerrado -> SoapNoteApiUnavailable', false);
} catch (SoapNoteApiUnavailable) {
    check('cliente HTTP real: puerto cerrado -> SoapNoteApiUnavailable', true);
}
echo 'Todas las pruebas del API pasaron.' . PHP_EOL;
