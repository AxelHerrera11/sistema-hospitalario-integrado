<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Application\AssociateDiagnosis;
use MicroHis\NotasSoap\Application\CareAssignmentReader;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Application\Icd10Catalog;
use MicroHis\NotasSoap\Application\RegisterSoapNote;
use MicroHis\NotasSoap\Application\TreatingPhysicianGuard;
use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\DiagnosisPolicy;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use MicroHis\NotasSoap\Persistence\PdoIcd10Catalog;
use MicroHis\NotasSoap\Presentation\SoapNoteHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** Base de prueba: SQLite en memoria con datos ficticios. La Dra. López atiende el episodio ep1. */
function app(?Icd10Catalog $catalog = null, ?InMemoryAuditTrail $audit = null): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO care_assignments VALUES ('ep1', 'dra.lopez', 'active'), ('ep1', 'dr.perez', 'ended')");
    $pdo->exec("INSERT INTO icd10_codes VALUES ('J18.9', 'Neumonía, no especificada', 1), ('I10', 'Hipertensión esencial', 1), ('A00', 'Cólera', 0)");
    return [$pdo, handler(new PdoClinicalNoteStore($pdo, $audit ?? new PdoAuditTrail($pdo)), new PdoCareAssignmentReader($pdo), $catalog ?? new PdoIcd10Catalog($pdo))];
}

/** Mismo flujo sin base de datos: repositorio, asignaciones, catálogo y auditoría en memoria. */
function memoryApp(bool $auditDown = false): array
{
    $audit = new InMemoryAuditTrail($auditDown);
    $notes = new InMemoryClinicalNoteRepository($audit);
    $catalog = new InMemoryIcd10Catalog(['J18.9' => ['Neumonía, no especificada', true], 'A00' => ['Cólera', false]]);
    return [$notes, $audit, handler($notes, new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']]), $catalog)];
}

function handler(ClinicalNoteRepository $notes, CareAssignmentReader $assignments, Icd10Catalog $catalog): SoapNoteHttpHandler
{
    $guard = new TreatingPhysicianGuard($assignments);
    return new SoapNoteHttpHandler(new RegisterSoapNote($guard, $notes), new AssociateDiagnosis($guard, $catalog, new DiagnosisPolicy(), $notes));
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

$col = static fn (PDO $pdo, string $sql): array => $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
$status = static fn (array $r): string => $r['status'] . ' ' . ($r['body']['code'] ?? '');
$lopez = ['X-User-ID' => 'dra.lopez'];
$soap = ['subjective' => 'Tos de 3 días', 'objective' => 'T 38.4 °C', 'assessment' => 'Neumonía', 'plan' => 'Amoxicilina'];

[$pdo, $http] = app();
$r = $http->registerNote($lopez, 'ep1', $soap);
$nota = $r['body']['clinical_note_id'] ?? '';
check('camino feliz: nota SOAP 201 en borrador con auditoría', $r['status'] === 201 && $col($pdo, 'SELECT note_type FROM clinical_notes') === ['soap'] && $col($pdo, 'SELECT event FROM local_access_audits') === ['nota_soap_registrada']);
$r = $http->associateDiagnosis($lopez, $nota, ['code' => 'J18.9', 'type' => 'primary']);
check('camino feliz: diagnóstico principal J18.9 201 con descripción del catálogo', $r['status'] === 201 && $col($pdo, 'SELECT description FROM diagnoses') === ['Neumonía, no especificada']);

check('regla de dominio: sección vacía -> 422', $status($http->registerNote($lopez, 'ep1', ['plan' => ' '] + $soap)) === '422 SOAP_SECTION_INVALID');
check('regla de dominio: asignación terminada -> 403', $status($http->registerNote(['X-User-ID' => 'dr.perez'], 'ep1', $soap)) === '403 NOT_TREATING_PHYSICIAN');
check('regla de dominio: código repetido -> 409', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'J18.9', 'type' => 'secondary'])) === '409 DIAGNOSIS_DUPLICATED');
check('regla de dominio: segundo principal -> 409', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'I10', 'type' => 'primary'])) === '409 PRIMARY_DIAGNOSIS_EXISTS');
check('regla de dominio: código inactivo en el catálogo -> 422', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'A00', 'type' => 'secondary'])) === '422 DIAGNOSIS_CODE_INVALID');
check('validación: tipo de diagnóstico no permitido -> 422', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'I10', 'type' => 'otro'])) === '422 DIAGNOSIS_INVALID');
check('validación de tipos: sección no string o encabezado no string -> 422 sin guardar', $status($http->registerNote($lopez, 'ep1', ['plan' => ['Amoxicilina']] + $soap)) === '422 PAYLOAD_INVALID'
    && $status($http->registerNote(['X-User-ID' => 7], 'ep1', $soap)) === '422 PAYLOAD_INVALID' && $col($pdo, 'SELECT COUNT(*) FROM clinical_notes') === [1]);
check('validación de tipos: código o tipo de diagnóstico no string -> 422 sin guardar', $status($http->associateDiagnosis($lopez, $nota, ['code' => 10, 'type' => 'secondary'])) === '422 PAYLOAD_INVALID'
    && $status($http->associateDiagnosis($lopez, $nota, ['code' => 'I10', 'type' => ['secondary']])) === '422 PAYLOAD_INVALID' && $col($pdo, 'SELECT COUNT(*) FROM diagnoses') === [1]);
$pdo->exec("UPDATE clinical_notes SET status = 'signed'");
check('regla de dominio: nota firmada no acepta diagnósticos -> 409', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'I10', 'type' => 'secondary'])) === '409 NOTE_SIGNED');
try {
    (new DiagnosisPolicy())->assertCanAdd(new ClinicalNote('n', 'ep1', 'dra.lopez', 'draft', [new Diagnosis('I10', 'primary')]), new Diagnosis('J18.9', 'primary'));
    check('regla de dominio pura (sin BD): la política rechaza un segundo principal', false);
} catch (SoapRuleViolation $e) {
    check('regla de dominio pura (sin BD): la política rechaza un segundo principal', $e->getMessage() === 'PRIMARY_DIAGNOSIS_EXISTS');
}

[$pdo, $http] = app(new InMemoryIcd10Catalog([], down: true));
$nota = $http->registerNote($lopez, 'ep1', $soap)['body']['clinical_note_id'];
check('doble de prueba: catálogo caído -> 503 y la nota queda sin diagnósticos', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'J18.9', 'type' => 'primary'])) === '503 ICD10_UNAVAILABLE' && $col($pdo, 'SELECT COUNT(*) FROM diagnoses') === [0]);
[$pdo, $http] = app();
$pdo->exec('DROP TABLE local_access_audits');
check('error de persistencia: auditoría caída -> 500 y rollback de la nota', $status($http->registerNote($lopez, 'ep1', $soap)) === '500 SOAP_PERSISTENCE_ERROR' && $col($pdo, 'SELECT COUNT(*) FROM clinical_notes') === [0]);
[$pdo, $http] = app(audit: new InMemoryAuditTrail(down: true));
check('auditoría por interfaz: PDO con bitácora caída -> 500 y rollback de la nota', $status($http->registerNote($lopez, 'ep1', $soap)) === '500 SOAP_PERSISTENCE_ERROR' && $col($pdo, 'SELECT COUNT(*) FROM clinical_notes') === [0]);

[$notes, $audit, $http] = memoryApp();
$nota = $http->registerNote($lopez, 'ep1', $soap)['body']['clinical_note_id'];
$r = $http->associateDiagnosis($lopez, $nota, ['code' => 'J18.9', 'type' => 'primary']);
check('InMemory: nota y diagnóstico 201 con auditoría', $r['status'] === 201 && array_column($audit->events, 'event') === ['nota_soap_registrada', 'diagnostico_asociado']);
check('InMemory: código inactivo -> 422 y no tratante -> 403', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'A00', 'type' => 'secondary'])) === '422 DIAGNOSIS_CODE_INVALID'
    && $status($http->registerNote(['X-User-ID' => 'dr.perez'], 'ep1', $soap)) === '403 NOT_TREATING_PHYSICIAN');
$notes->markSigned($nota);
check('InMemory: nota firmada -> 409', $status($http->associateDiagnosis($lopez, $nota, ['code' => 'I10', 'type' => 'secondary'])) === '409 NOTE_SIGNED');
[$notes, $audit, $http] = memoryApp(auditDown: true);
check('InMemory: auditoría caída -> 500 y la nota no queda guardada', $status($http->registerNote($lopez, 'ep1', $soap)) === '500 SOAP_PERSISTENCE_ERROR' && $notes->find('nota-1') === null);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
