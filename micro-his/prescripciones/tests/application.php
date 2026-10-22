<?php

declare(strict_types=1);

use MicroHis\Prescripciones\Application\AllergyChecker;
use MicroHis\Prescripciones\Application\AuditLogger;
use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\AuditUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\MedicationCatalog;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAllergyChecker;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAuditLogger;
use MicroHis\Prescripciones\Application\Persistence\InMemoryMedicationCatalog;
use MicroHis\Prescripciones\Application\Persistence\InMemoryPrescriptionRepository;
use MicroHis\Prescripciones\Application\PrescriptionService;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyAssessment;
use MicroHis\Prescripciones\Domain\AllergyConflict;
use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\AllergyMatcher;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\NotTreatingPhysician;
use MicroHis\Prescripciones\Domain\Prescription;
use MicroHis\Prescripciones\Domain\PrescriptionNotDraft;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use MicroHis\Prescripciones\Domain\PrescriptionRuleViolation;
use MicroHis\Prescripciones\Domain\PrescriptionStatus;
use MicroHis\Prescripciones\Domain\Severity;

require_once __DIR__.'/../bootstrap.php';

// --- Tests de dominio (existentes) ---

check('severidad: los cinco valores del esquema', array_map(static fn (Severity $s): string => $s->value, Severity::cases()) ===
    ['mild', 'moderate', 'severe', 'life_threatening', 'unknown']);
check('tipo de alergia: los tres valores del esquema', array_map(static fn (AllergyType $t): string => $t->value, AllergyType::cases()) ===
    ['allergy', 'intolerance', 'unknown']);
check('SD-PRE-01: la alergia severe es grave', (new Allergy('PEN', null, AllergyType::Allergy, Severity::Severe))->isGrave());
check('SD-PRE-01: la alergia life_threatening es grave', (new Allergy('PEN', null, AllergyType::Allergy, Severity::LifeThreatening))->isGrave());
check('SD-PRE-01: la intolerancia severe no es grave', ! (new Allergy('PEN', null, AllergyType::Intolerance, Severity::Severe))->isGrave());
check('SD-PRE-01: la alergia unknown no es grave', ! (new Allergy('PEN', null, AllergyType::Allergy, Severity::Unknown))->isGrave());
check('alergia: etiqueta por código', (new Allergy('PEN', null, AllergyType::Allergy, Severity::Severe))->label() === 'PEN');
check('alergia: etiqueta por nombre', (new Allergy(null, 'Penicilina', AllergyType::Allergy, Severity::Mild))->label() === 'Penicilina');
check('alergia: un código en blanco cae al nombre', (new Allergy('  ', 'Penicilina', AllergyType::Allergy, Severity::Mild))->label() === 'Penicilina');
check('alergia: etiqueta vacía sin datos', (new Allergy(null, null, AllergyType::Unknown, Severity::Unknown))->label() === '');
check('SD-PRE-02: la normalización quita acentos y espacios', Allergy::normalize('  Ámoxicilina  ') === 'amoxicilina');
$conflict = new AllergyConflict('PEN', AllergyType::Allergy, Severity::Severe, AllergyConflict::ACTIVE_INGREDIENT);
check('conflicto: forma JSON con allergy_type', $conflict->toArray() === ['allergen' => 'PEN', 'allergy_type' => 'allergy', 'severity' => 'severe', 'matched_on' => 'active_ingredient']);
check('conflicto: la clave de cobertura normaliza el alérgeno', $conflict->key() === 'pen|active_ingredient');
check('conflicto: el conflicto grave no admite excepción', $conflict->isGrave());
check('conflicto: la intolerancia grave no es bloqueante sin excepción', ! (new AllergyConflict('LACT', AllergyType::Intolerance, Severity::Severe, AllergyConflict::PHARMACOLOGICAL_GROUP))->isGrave());
check('vía: oral es válida', PrescriptionRoute::fromInput('Oral')->value === 'oral');
check('vía: se recorta y pasa a minúsculas', PrescriptionRoute::fromInput(' INTRAVENOSA ')->value === 'intravenosa');
$invalidRoute = thrown(static fn () => PrescriptionRoute::fromInput('oral oral'), PrescriptionRuleViolation::class);
check('vía: valor inválido viola la regla del campo route', $invalidRoute instanceof PrescriptionRuleViolation && $invalidRoute->field === 'route');
check('estado: draft y signed', array_map(static fn (PrescriptionStatus $s): string => $s->value, PrescriptionStatus::cases()) === ['draft', 'signed']);
$blocked = thrown(static fn () => throw new AllergyConflictBlocked([$conflict], false), AllergyConflictBlocked::class);
check('conflicto bloqueante: lleva los conflictos', $blocked instanceof AllergyConflictBlocked && $blocked->conflicts === [$conflict]);
check('conflicto bloqueante: un conflicto grave no admite excepción', $blocked instanceof AllergyConflictBlocked && $blocked->exceptionAllowed === false);
$allowed = thrown(static fn () => throw new AllergyConflictBlocked([$conflict], true), AllergyConflictBlocked::class);
check('conflicto bloqueante: un conflicto no grave admite excepción', $allowed instanceof AllergyConflictBlocked && $allowed->exceptionAllowed === true);
check('firma: un borrador ya firmado o anulado da 409', str_contains((new PrescriptionNotDraft)->getMessage(), 'ya está firmada'));
check('firma: un médico que no es tratante da 403', str_contains((new NotTreatingPhysician)->getMessage(), 'tratante'));
$snapshot = new MedicationSnapshot('m-1', 'AMX-500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Antibiótico', true);
check('medicamento: conserva los datos del catálogo', $snapshot->activeIngredient === 'Amoxicilina' && $snapshot->active);

// --- AllergyMatcher / Assessment / Prescription (existentes) ---

$matcher = new AllergyMatcher();
$med = new MedicationSnapshot('m-1', 'AMX-500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Antibiótico', true);
$allergies = [new Allergy('Amoxicilina', null, AllergyType::Allergy, Severity::Moderate)];
$conflicts = $matcher->match($allergies, $med);
check('matcher: coincide por principio activo normalizado', count($conflicts) === 1 && $conflicts[0]->matchedOn === AllergyConflict::ACTIVE_INGREDIENT);
$allergies2 = [new Allergy('ANTIBIÓTICO', null, AllergyType::Allergy, Severity::Mild)];
$conflicts2 = $matcher->match($allergies2, $med);
check('matcher: coincide por grupo farmacológico normalizado', count($conflicts2) === 1 && $conflicts2[0]->matchedOn === AllergyConflict::PHARMACOLOGICAL_GROUP);
$allergies3 = [new Allergy('OTRO', null, AllergyType::Allergy, Severity::Mild)];
$conflicts3 = $matcher->match($allergies3, $med);
check('matcher: sin coincidencia si no iguala principio ni grupo', count($conflicts3) === 0);
$allergies4 = [new Allergy('  ', null, AllergyType::Allergy, Severity::Mild)];
$conflicts4 = $matcher->match($allergies4, $med);
check('matcher: alérgeno en blanco no coincide', count($conflicts4) === 0);
$conflicts5 = $matcher->match([], $med);
check('matcher: sin alergias → lista vacía', count($conflicts5) === 0);

$assessment = new AllergyAssessment();
$graveConflicts = [new AllergyConflict('PEN', AllergyType::Allergy, Severity::Severe, AllergyConflict::ACTIVE_INGREDIENT)];
$resultGrave = $assessment->assess($graveConflicts);
check('assessment: conflicto grave bloqueado sin excepción', $resultGrave['blocked'] === true && $resultGrave['exceptionAllowed'] === false);
$mildConflicts = [new AllergyConflict('PEN', AllergyType::Allergy, Severity::Mild, AllergyConflict::ACTIVE_INGREDIENT)];
$resultMild = $assessment->assess($mildConflicts);
check('assessment: conflicto no grave bloqueado con excepción', $resultMild['blocked'] === true && $resultMild['exceptionAllowed'] === true);
$resultNone = $assessment->assess([]);
check('assessment: sin conflictos → no bloqueado', $resultNone['blocked'] === false && $resultNone['exceptionAllowed'] === false);
$intoleranceGrave = [new AllergyConflict('LACT', AllergyType::Intolerance, Severity::LifeThreatening, AllergyConflict::PHARMACOLOGICAL_GROUP)];
$resultIntol = $assessment->assess($intoleranceGrave);
check('assessment: intolerancia life_threatening admite excepción', $resultIntol['blocked'] === true && $resultIntol['exceptionAllowed'] === true);

$rx = Prescription::createDraft('rx-1', 'patient-1', 'doctor-1', 'episode-1', $med, PrescriptionRoute::fromInput('Oral'), '500 mg', 'Cada 8 horas', '7', 'Tomar con alimentos');
check('prescripción: crear borrador válido', $rx->isDraft() && $rx->id === 'rx-1' && $rx->patientId === 'patient-1');
$errDose = thrown(static fn () => Prescription::createDraft('rx', 'p', 'd', 'e', $med, PrescriptionRoute::fromInput('Oral'), '', 'f', '1'), PrescriptionRuleViolation::class);
check('prescripción: dosis vacía viola regla', $errDose instanceof PrescriptionRuleViolation && $errDose->field === 'dose');
$errFreq = thrown(static fn () => Prescription::createDraft('rx', 'p', 'd', 'e', $med, PrescriptionRoute::fromInput('Oral'), '1', '', '1'), PrescriptionRuleViolation::class);
check('prescripción: frecuencia vacía viola regla', $errFreq instanceof PrescriptionRuleViolation && $errFreq->field === 'frequency');
$errDur = thrown(static fn () => Prescription::createDraft('rx', 'p', 'd', 'e', $med, PrescriptionRoute::fromInput('Oral'), '1', 'f', '0'), PrescriptionRuleViolation::class);
check('prescripción: duración 0 viola regla', $errDur instanceof PrescriptionRuleViolation && $errDur->field === 'durationDays');
$signed = $rx->sign('doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'), []);
check('prescripción: firmar borrador sin conflictos', $signed->isSigned() && $signed->signedBy === 'doctor-1' && $signed->allergyConflictsAtSign === []);
$errAlreadySigned = thrown(static fn () => $signed->sign('doctor-1', new \DateTimeImmutable('2026-10-20 11:00:00'), []), PrescriptionNotDraft::class);
check('prescripción: firmar ya firmada da 409', $errAlreadySigned instanceof PrescriptionNotDraft);
$errNotTreating = thrown(static fn () => $rx->sign('doctor-2', new \DateTimeImmutable('2026-10-20 10:00:00'), []), NotTreatingPhysician::class);
check('prescripción: médico no tratante da 403', $errNotTreating instanceof NotTreatingPhysician);
$errGraveConflict = thrown(static fn () => $rx->sign('doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'), $graveConflicts), AllergyConflictBlocked::class);
check('prescripción: conflicto grave al firmar bloquea sin excepción', $errGraveConflict instanceof AllergyConflictBlocked && $errGraveConflict->exceptionAllowed === false);
$rxMild = Prescription::createDraft('rx-mild', 'p', 'doctor-1', 'e', $med, PrescriptionRoute::fromInput('Oral'), '1', 'f', '1');
$signedMild = $rxMild->sign('doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'), $mildConflicts);
check('prescripción: conflicto no grave admite excepción (no lanza en dominio)', $signedMild->isSigned());
check('prescripción: guarda conflictos al firmar', $signedMild->allergyConflictsAtSign !== null && count($signedMild->allergyConflictsAtSign) === 1);
$allowedToSign = (new AllergyAssessment())->assess($mildConflicts);
check('prescripción: assessment dice que conflicto no grave admite excepción', $allowedToSign['exceptionAllowed'] === true);

// --- NUEVOS: Tests de PrescriptionService (Application layer) ---
// Cada test usa instancias nuevas para evitar fuga de estado.

$medSnap = new MedicationSnapshot('med-1', 'AMX-500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Antibiótico', true);

// 1. Camino feliz: crear + firmar sin conflictos
$repo1 = new InMemoryPrescriptionRepository();
$catalog1 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy1 = new InMemoryAllergyChecker([]);
$audit1 = new InMemoryAuditLogger();
$svc1 = new PrescriptionService($repo1, $catalog1, $allergy1, $audit1);

$rx1 = $svc1->create('rx-1', 'patient-1', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7', 'Notas');
check('service: crear borrador sin conflictos → draft', $rx1->isDraft() && $rx1->id === 'rx-1');
$signed1 = $svc1->sign('rx-1', 'doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'));
check('service: firmar borrador sin conflictos → signed', $signed1->isSigned() && $signed1->signedBy === 'doctor-1');
check('service: auditoría registra creación y firma', count($audit1->logs) === 2 && $audit1->logs[0]['event'] === 'prescripcion.creada' && $audit1->logs[1]['event'] === 'prescripcion.firmada');

// 2. Conflicto grave al crear → bloquea, audita 'prescripcion.bloqueada', lanza AllergyConflictBlocked
$repo2 = new InMemoryPrescriptionRepository();
$catalog2 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy2 = new InMemoryAllergyChecker(['patient-2' => [new Allergy('Amoxicilina', null, AllergyType::Allergy, Severity::Severe)]]);
$audit2 = new InMemoryAuditLogger();
$svc2 = new PrescriptionService($repo2, $catalog2, $allergy2, $audit2);

$errGraveCreate = thrown(
    static fn () => $svc2->create('rx-2', 'patient-2', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7'),
    AllergyConflictBlocked::class
);
check('service: conflicto grave al crear bloquea sin excepción', $errGraveCreate instanceof AllergyConflictBlocked && $errGraveCreate->exceptionAllowed === false);
check('service: auditoría registra bloqueo', $audit2->logs[0]['event'] === 'prescripcion.bloqueada' && $audit2->logs[0]['payload']['exception_allowed'] === false);

// 3. Conflicto no grave al crear → bloquea con exceptionAllowed=true
$repo3 = new InMemoryPrescriptionRepository();
$catalog3 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy3 = new InMemoryAllergyChecker(['patient-3' => [new Allergy('Amoxicilina', null, AllergyType::Allergy, Severity::Mild)]]);
$audit3 = new InMemoryAuditLogger();
$svc3 = new PrescriptionService($repo3, $catalog3, $allergy3, $audit3);

$errMildCreate = thrown(
    static fn () => $svc3->create('rx-3', 'patient-3', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7'),
    AllergyConflictBlocked::class
);
check('service: conflicto no grave al crear bloquea con excepción', $errMildCreate instanceof AllergyConflictBlocked && $errMildCreate->exceptionAllowed === true);
check('service: auditoría registra bloqueo con exception_allowed=true', $audit3->logs[0]['payload']['exception_allowed'] === true);

// 4. Medicamento no encontrado → MedicationNotFound
$repo4 = new InMemoryPrescriptionRepository();
$catalog4 = new InMemoryMedicationCatalog([]);
$allergy4 = new InMemoryAllergyChecker([]);
$audit4 = new InMemoryAuditLogger();
$svc4 = new PrescriptionService($repo4, $catalog4, $allergy4, $audit4);

$errMedNotFound = thrown(
    static fn () => $svc4->create('rx-4', 'patient-1', 'doctor-1', 'ep-1', 'med-inexistente', 'oral', '500 mg', 'Cada 8h', '7'),
    MedicationNotFound::class
);
check('service: medicamento inexistente → MedicationNotFound', $errMedNotFound instanceof MedicationNotFound);

// 5. Error de persistencia al guardar → PersistenceFailure
$failingRepo = new class implements PrescriptionRepository {
    public function save(Prescription $p): void { throw new \RuntimeException('DB down'); }
    public function findById(string $id): ?Prescription { return null; }
    public function listByPatient(string $pid): array { return []; }
};
$repo5 = $failingRepo;
$catalog5 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy5 = new InMemoryAllergyChecker([]);
$audit5 = new InMemoryAuditLogger();
$svc5 = new PrescriptionService($repo5, $catalog5, $allergy5, $audit5);

$errPersist = thrown(
    static fn () => $svc5->create('rx-5', 'patient-1', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7'),
    PersistenceFailure::class
);
check('service: fallo de persistencia al guardar → PersistenceFailure', $errPersist instanceof PersistenceFailure);

// 6. Error de auditoría al crear → AuditUnavailable
$failingAudit = new class implements AuditLogger {
    public function record(string $event, array $payload): void { throw new \RuntimeException('Audit down'); }
};
$repo6 = new InMemoryPrescriptionRepository();
$catalog6 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy6 = new InMemoryAllergyChecker([]);
$audit6 = $failingAudit;
$svc6 = new PrescriptionService($repo6, $catalog6, $allergy6, $audit6);

$errAudit = thrown(
    static fn () => $svc6->create('rx-6', 'patient-1', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7'),
    AuditUnavailable::class
);
check('service: fallo de auditoría al crear → AuditUnavailable', $errAudit instanceof AuditUnavailable);

// 7. Error de alergias (AllergyCheckUnavailable)
$repo7 = new InMemoryPrescriptionRepository();
$catalog7 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy7 = new InMemoryAllergyChecker([]);
$allergy7->failNext(true);
$audit7 = new InMemoryAuditLogger();
$svc7 = new PrescriptionService($repo7, $catalog7, $allergy7, $audit7);

$errAllergy = thrown(
    static fn () => $svc7->create('rx-7', 'patient-1', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7'),
    AllergyCheckUnavailable::class
);
check('service: servicio alergias caído → AllergyCheckUnavailable', $errAllergy instanceof AllergyCheckUnavailable);

// 8. Firmar con conflicto grave detectado al firmar (revalidación) → bloquea
$repo8 = new InMemoryPrescriptionRepository();
$catalog8 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy8 = new InMemoryAllergyChecker([]);
$audit8 = new InMemoryAuditLogger();
$svc8 = new PrescriptionService($repo8, $catalog8, $allergy8, $audit8);

// Primero crear sin conflictos
$rx8 = $svc8->create('rx-8', 'patient-4', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7');
// Luego simular que aparecen alergias al firmar
$allergy8->setAllergies('patient-4', [new Allergy('Amoxicilina', null, AllergyType::Allergy, Severity::Severe)]);

$errGraveSign = thrown(
    static fn () => $svc8->sign('rx-8', 'doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00')),
    AllergyConflictBlocked::class
);
check('service: revalidación al firmar detecta conflicto grave → bloquea', $errGraveSign instanceof AllergyConflictBlocked && $errGraveSign->exceptionAllowed === false);
check('service: auditoría registra firma_bloqueada', end($audit8->logs)['event'] === 'prescripcion.firma_bloqueada');

// 9. Firmar borrador ya firmado → PrescriptionNotDraft
$repo9 = new InMemoryPrescriptionRepository();
$catalog9 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy9 = new InMemoryAllergyChecker([]);
$audit9 = new InMemoryAuditLogger();
$svc9 = new PrescriptionService($repo9, $catalog9, $allergy9, $audit9);
$rx9 = $svc9->create('rx-9', 'patient-1', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7');
$svc9->sign('rx-9', 'doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'));

$errAlreadySigned = thrown(
    static fn () => $svc9->sign('rx-9', 'doctor-1', new \DateTimeImmutable('2026-10-20 11:00:00')),
    PrescriptionNotDraft::class
);
check('service: firmar ya firmada → PrescriptionNotDraft', $errAlreadySigned instanceof PrescriptionNotDraft);

// 10. Firmar con médico no tratante → NotTreatingPhysician
$repo10 = new InMemoryPrescriptionRepository();
$catalog10 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy10 = new InMemoryAllergyChecker([]);
$audit10 = new InMemoryAuditLogger();
$svc10 = new PrescriptionService($repo10, $catalog10, $allergy10, $audit10);
$rx10 = $svc10->create('rx-10', 'patient-1', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7');

$errNotTreating = thrown(
    static fn () => $svc10->sign('rx-10', 'doctor-2', new \DateTimeImmutable('2026-10-20 10:00:00')),
    NotTreatingPhysician::class
);
check('service: firmar con otro médico → NotTreatingPhysician', $errNotTreating instanceof NotTreatingPhysician);

// 11. Listar y buscar
$repo11 = new InMemoryPrescriptionRepository();
$catalog11 = new InMemoryMedicationCatalog(['med-1' => $medSnap]);
$allergy11 = new InMemoryAllergyChecker([]);
$audit11 = new InMemoryAuditLogger();
$svc11 = new PrescriptionService($repo11, $catalog11, $allergy11, $audit11);
$svc11->create('rx-11a', 'patient-1', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7');
$svc11->create('rx-11b', 'patient-2', 'doctor-1', 'ep-1', 'med-1', 'oral', '500 mg', 'Cada 8h', '7');

$found = $svc11->findById('rx-11a');
check('service: findById encuentra la prescripción', $found !== null && $found->id === 'rx-11a');
$list = $svc11->listByPatient('patient-1');
check('service: listByPatient devuelve prescripciones del paciente', count($list) === 1);

finish();