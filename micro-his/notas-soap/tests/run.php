<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\DiagnosisPolicy;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\Uuid;
use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use MicroHis\NotasSoap\Persistence\PdoIcd10Catalog;
use MicroHis\NotasSoap\Presentation\Request;
use MicroHis\NotasSoap\Presentation\Router;
use MicroHis\NotasSoap\Presentation\SoapNoteController;

require __DIR__ . '/../app.php';

/** Datos ficticios comunes a los dos adaptadores: la Dra. López atiende ep1; la asignación del Dr. Pérez terminó. */
const CODES = ['J18.9' => ['Neumonía, no especificada', true], 'I10' => ['Hipertensión esencial', true], 'A00' => ['Cólera', false]];
const SOAP = ['subjective' => 'Tos de 3 días', 'objective' => 'T 38.4 °C', 'assessment' => 'Neumonía', 'plan' => 'Amoxicilina'];

/**
 * Adaptador PDO sobre SQLite en memoria. $fail: 'catalog' (catálogo caído) o 'audit' (bitácora caída).
 * Devuelve el router y consultas sobre el estado: notas, descripciones de diagnósticos, eventos auditados y firma.
 */
function pdoApp(string $fail = ''): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/care-seed.sql'));
    foreach (CODES as $code => [$description, $active]) {
        $pdo->prepare('INSERT INTO icd10_codes VALUES (?, ?, ?)')->execute([$code, $description, (int) $active]);
    }
    $fail === 'audit' && $pdo->exec('DROP TABLE local_access_audits');
    $catalog = $fail === 'catalog' ? new InMemoryIcd10Catalog(down: true) : new PdoIcd10Catalog($pdo);
    $col = static fn (string $sql): array => $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    return [notas_soap_router(new PdoClinicalNoteStore($pdo, new PdoAuditTrail($pdo)), new PdoCareAssignmentReader($pdo), $catalog), [
        'notes' => fn (): int => (int) $col('SELECT COUNT(*) FROM clinical_notes')[0],
        'descriptions' => fn (): array => $col('SELECT description FROM diagnoses'),
        'audits' => fn (): array => $col('SELECT action FROM local_access_audits'),
        'sign' => fn (string $id) => $pdo->prepare("UPDATE clinical_notes SET status = 'signed' WHERE clinical_note_id = ?")->execute([$id]),
    ]];
}

/** Adaptador InMemory con los mismos datos y las mismas consultas. */
function memoryApp(string $fail = ''): array
{
    $audit = new InMemoryAuditTrail(down: $fail === 'audit');
    $notes = new InMemoryClinicalNoteRepository($audit);
    $catalog = new InMemoryIcd10Catalog(CODES, down: $fail === 'catalog');
    return [notas_soap_router($notes, new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']]), $catalog), [
        'notes' => fn (): int => count($notes->notes),
        'descriptions' => fn (): array => array_column(array_merge([], ...array_values($notes->diagnoses)), 'description'),
        'audits' => fn (): array => array_column($audit->events, 'action'),
        'sign' => fn (string $id) => $notes->markSigned($id),
    ]];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

/** Pasa la petición por el router (como lo haría public/index.php) y decodifica la vista JSON. */
function call(Router $router, string $path, array $body, array $headers = ['X-User-ID' => 'dra.lopez'], string $method = 'POST'): array
{
    $response = $router->dispatch(new Request($method, $path, $headers, $body));
    return ['status' => $response['status'], 'type' => $response['headers']['Content-Type'], 'body' => json_decode($response['body'], true)];
}

$status = static fn (array $r): string => $r['status'] . ' ' . ($r['body']['code'] ?? '');

foreach (['pdo' => 'pdoApp', 'memoria' => 'memoryApp'] as $adapter => $make) {
    $t = static fn (string $label): string => "[$adapter] $label";
    [$router, $state] = $make();
    $diagnose = static fn (string $note, array $body, array $headers = ['X-User-ID' => 'dra.lopez']): array => call($router, "/soap-notes/$note/diagnoses", $body, $headers);

    $r = call($router, '/care-episodes/ep1/soap-notes', SOAP);
    $nota = $r['body']['clinical_note_id'] ?? '';
    check($t('camino feliz: POST /care-episodes/ep1/soap-notes -> 201 JSON en borrador, id UUID y auditoría'), $r['status'] === 201 && str_starts_with($r['type'], 'application/json')
        && $r['body']['status'] === 'draft' && preg_match(Uuid::PATTERN, $nota) === 1 && $state['audits']() === ['nota_soap.registrada']);
    $r = $diagnose($nota, ['code' => 'J18.9', 'type' => 'primary']);
    check($t('camino feliz: diagnóstico principal J18.9 -> 201 con descripción del catálogo'), $r['status'] === 201 && $state['descriptions']() === ['Neumonía, no especificada']);
    $r = call($router, "/soap-notes/$nota", [], method: 'GET');
    check($t('lectura: GET /soap-notes/{id} -> 200 con las cuatro secciones y el diagnóstico'), $r['status'] === 200 && $r['body']['sections'] === SOAP
        && $r['body']['diagnoses'] === [['code' => 'J18.9', 'type' => 'primary']]);

    check($t('regla de dominio: sección vacía -> 422'), $status(call($router, '/care-episodes/ep1/soap-notes', ['plan' => ' '] + SOAP)) === '422 SOAP_SECTION_INVALID');
    check($t('regla de dominio: asignación terminada -> 403'), $status(call($router, '/care-episodes/ep1/soap-notes', SOAP, ['X-User-ID' => 'dr.perez'])) === '403 NOT_TREATING_PHYSICIAN');
    check($t('regla de dominio: código repetido -> 409'), $status($diagnose($nota, ['code' => 'J18.9', 'type' => 'secondary'])) === '409 DIAGNOSIS_DUPLICATED');
    check($t('regla de dominio: segundo principal -> 409'), $status($diagnose($nota, ['code' => 'I10', 'type' => 'primary'])) === '409 PRIMARY_DIAGNOSIS_EXISTS');
    check($t('regla de dominio: código inactivo en el catálogo -> 422'), $status($diagnose($nota, ['code' => 'A00', 'type' => 'secondary'])) === '422 DIAGNOSIS_CODE_INVALID');
    check($t('regla de dominio: nota inexistente -> 404'), $status($diagnose('nota-nadie', ['code' => 'I10', 'type' => 'secondary'])) === '404 NOTE_NOT_FOUND');
    check($t('validación: tipo de diagnóstico no permitido -> 422'), $status($diagnose($nota, ['code' => 'I10', 'type' => 'otro'])) === '422 DIAGNOSIS_INVALID');
    check($t('validación de tipos: sección no string o encabezado no string -> 422 sin guardar'), $status(call($router, '/care-episodes/ep1/soap-notes', ['plan' => ['Amoxicilina']] + SOAP)) === '422 PAYLOAD_INVALID'
        && $status(call($router, '/care-episodes/ep1/soap-notes', SOAP, ['X-User-ID' => 7])) === '422 PAYLOAD_INVALID' && $state['notes']() === 1);
    check($t('validación de tipos: código o tipo de diagnóstico no string -> 422 sin guardar'), $status($diagnose($nota, ['code' => 10, 'type' => 'secondary'])) === '422 PAYLOAD_INVALID'
        && $status($diagnose($nota, ['code' => 'I10', 'type' => ['secondary']])) === '422 PAYLOAD_INVALID' && count($state['descriptions']()) === 1);
    $state['sign']($nota);
    check($t('regla de dominio: nota firmada no acepta diagnósticos -> 409'), $status($diagnose($nota, ['code' => 'I10', 'type' => 'secondary'])) === '409 NOTE_SIGNED');
    check($t('ruta desconocida -> 404 ROUTE_NOT_FOUND'), $status(call($router, '/soap-notes', [], method: 'DELETE')) === '404 ROUTE_NOT_FOUND');

    [$router, $state] = $make('catalog');
    $nota = call($router, '/care-episodes/ep1/soap-notes', SOAP)['body']['clinical_note_id'];
    check($t('catálogo caído -> 503 y la nota queda sin diagnósticos'), $status(call($router, "/soap-notes/$nota/diagnoses", ['code' => 'J18.9', 'type' => 'primary'])) === '503 ICD10_UNAVAILABLE' && $state['descriptions']() === []);
    [$router, $state] = $make('audit');
    check($t('error de persistencia: auditoría caída -> 500 y sin nota a medias'), $status(call($router, '/care-episodes/ep1/soap-notes', SOAP)) === '500 SOAP_PERSISTENCE_ERROR' && $state['notes']() === 0);
}

try {
    (new DiagnosisPolicy())->assertCanAdd(new ClinicalNote('n', 'ep1', 'dra.lopez', 'draft', [new Diagnosis('I10', 'primary')]), new Diagnosis('J18.9', 'primary'));
    check('regla de dominio pura (sin BD): la política rechaza un segundo principal', false);
} catch (SoapRuleViolation $e) {
    check('regla de dominio pura (sin BD): la política rechaza un segundo principal', $e->getMessage() === 'PRIMARY_DIAGNOSIS_EXISTS');
}
$tipos = array_map(static fn (ReflectionParameter $p): string => (string) $p->getType(), (new ReflectionMethod(SoapNoteController::class, '__construct'))->getParameters());
$controller = (string) file_get_contents((new ReflectionClass(SoapNoteController::class))->getFileName());
check('MVC: el controlador solo recibe casos de uso y la vista, sin SQL ni PDO', array_filter($tipos, static fn (string $t): bool => !str_contains($t, '\\Application\\') && !str_ends_with($t, '\\JsonView')) === []
    && preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|PDO)\b/', $controller) === 0);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
