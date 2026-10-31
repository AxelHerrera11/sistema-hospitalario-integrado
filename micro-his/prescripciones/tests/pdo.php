<?php

declare(strict_types=1);

use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAllergyChecker;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAuditLogger;
use MicroHis\Prescripciones\Application\Persistence\InMemoryMedicationCatalog;
use MicroHis\Prescripciones\Application\PrescriptionRecord;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Application\PrescriptionService;
use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\Prescription;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use MicroHis\Prescripciones\Domain\Severity;
use MicroHis\Prescripciones\Persistence\PdoAllergyChecker;
use MicroHis\Prescripciones\Persistence\PdoPrescriptionRepository;
require __DIR__.'/harness.php';
require __DIR__.'/../bootstrap.php';

function prescriptionDatabase(bool $withSchema = true): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('CREATE TABLE local_patients (local_patient_id UUID PRIMARY KEY)');
    $pdo->exec('CREATE TABLE care_episodes (care_episode_id UUID PRIMARY KEY)');
    $pdo->exec('CREATE TABLE medications (medication_id UUID PRIMARY KEY, code TEXT NOT NULL, name TEXT NOT NULL, active_ingredient TEXT NOT NULL, pharmacological_group TEXT NOT NULL, validity TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE doctor_profiles (doctor_profile_id UUID PRIMARY KEY)');
    if ($withSchema) {
        $pdo->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
        $pdo->exec('CREATE TABLE allergies (allergy_id UUID PRIMARY KEY, local_patient_id UUID NOT NULL, allergen_code TEXT, allergen_name TEXT, allergy_type TEXT NOT NULL, severity TEXT NOT NULL, status TEXT NOT NULL)');
        $pdo->exec("INSERT INTO local_patients VALUES ('patient-1'), ('patient-2')");
        $pdo->exec("INSERT INTO care_episodes VALUES ('episode-1')");
        $pdo->exec("INSERT INTO medications VALUES ('med-1', 'AMX500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Penicilinas', 'active')");
        $pdo->exec("INSERT INTO doctor_profiles VALUES ('doctor-1')");
    }
    return $pdo;
}

function prescriptionRecord(): PrescriptionRecord
{
    $medication = new MedicationSnapshot('med-1', 'AMX500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Penicilinas', true);
    $prescription = Prescription::createDraft('b7b4c4a2-aebb-4d9b-ae04-4953e2db07fd', 'patient-1', 'doctor-1', 'episode-1',
        $medication, PrescriptionRoute::Oral, '500 mg', '2', '7');
    return new PrescriptionRecord($prescription, 'clear', '500', 'mg', 2, 'day', 7, 'days');
}

function serviceWithRepository(PrescriptionRepository $repository, ?InMemoryAuditLogger $audit = null): PrescriptionService
{
    return new PrescriptionService(
        $repository,
        new InMemoryAllergyChecker,
        new InMemoryMedicationCatalog([new MedicationSnapshot('med-1', 'AMX500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Penicilinas', true)]),
        $audit ?? new InMemoryAuditLogger,
    );
}
$pdo = prescriptionDatabase();
$repository = new PdoPrescriptionRepository($pdo);
$saved = prescriptionRecord();
$repository->save($saved);
$loaded = $repository->findById($saved->prescription->id);
check('PDO: crea y lee borrador con cantidad y unidad separadas', $loaded?->prescription->isDraft() && $loaded->doseAmount === '500' && $loaded->doseUnit === 'mg');
check('PDO: conserva el resultado allergy_check_result', $loaded?->allergyCheckResult === 'clear');
check('PDO: lista por paciente y excluye otros pacientes', count($repository->forPatient('patient-1')) === 1 && $repository->forPatient('patient-2') === []);
$signedPrescription = $saved->prescription->sign('doctor-1', new DateTimeImmutable('2026-10-23T12:00:00Z'), []);
$signed = $saved->withPrescription($signedPrescription, 'warning', ['ALLERGY_CONFLICT']);
$repository->save($signed);
$loadedSigned = $repository->findById($signedPrescription->id);
check('PDO: persiste y reconstruye la transición draft a signed', $loadedSigned?->prescription->isSigned() && $loadedSigned->allergyCheckResult === 'warning');
check('PDO: una prescripción firmada no se sobrescribe', thrown(static fn () => $repository->save($signed), PersistenceFailure::class) !== null);
$pdo->exec("INSERT INTO allergies VALUES ('allergy-1', 'patient-1', 'AMX', 'Amoxicilina', 'allergy', 'moderate', 'active'), ('allergy-2', 'patient-1', 'OLD', 'Antigua', 'allergy', 'mild', 'inactive')");
$checker = new PdoAllergyChecker($pdo);
$active = $checker->activeAllergies('patient-1');
check('PDO: el lector devuelve alergias activas tipadas y omite inactivas', count($active) === 1 && $active[0] instanceof Allergy && $active[0]->severity === Severity::Moderate);
check('PDO: el lector devuelve lista vacía si no hay alergias activas', $checker->activeAllergies('patient-2') === []);
$broken = new PdoPrescriptionRepository(prescriptionDatabase(withSchema: false));
$failure = thrown(static fn () => $broken->save(prescriptionRecord()), PersistenceFailure::class);
check('PDO: PDOException de escritura se traduce a PersistenceFailure conservando la causa', $failure instanceof PersistenceFailure && $failure->getPrevious() instanceof PDOException);
$unavailable = new PdoAllergyChecker(prescriptionDatabase(withSchema: false));
$allergyFailure = thrown(static fn () => $unavailable->activeAllergies('patient-1'), AllergyCheckUnavailable::class);
check('PDO: fallo de lectura de alergias falla cerrado con AllergyCheckUnavailable', $allergyFailure instanceof AllergyCheckUnavailable && $allergyFailure->getPrevious() instanceof PDOException);
$tx = prescriptionDatabase();
$txRepository = new PdoPrescriptionRepository($tx);
$audit = new InMemoryAuditLogger;
$service = serviceWithRepository($txRepository, $audit);
$input = ['patient_id' => 'patient-1', 'doctor_profile_id' => 'doctor-1', 'care_episode_id' => 'episode-1', 'medication_id' => 'med-1', 'dose_amount' => '500', 'dose_unit' => 'mg', 'route' => 'oral', 'frequency_times' => 2, 'frequency_interval' => 'day', 'duration_value' => 7, 'duration_unit' => 'days'];
$audit->failNext(true);
$failure = thrown(fn () => $service->createDraft($input), \MicroHis\Prescripciones\Application\Exception\AuditUnavailable::class);
check('PDO: fallo auditoria revierte INSERT', $failure !== null && $txRepository->forPatient('patient-1') === [] && !$tx->inTransaction());
$audit->failNext(false);
$draft = $service->createDraft($input);
$audit->failNext(true);
$tx->beginTransaction();
$failure = thrown(fn () => $service->sign($draft->prescription->id, 'doctor-1'), \MicroHis\Prescripciones\Application\Exception\AuditUnavailable::class);
check('PDO: fallo auditoria revierte firma y conserva transaccion externa', $failure !== null && $txRepository->findById($draft->prescription->id)->prescription->isDraft() && $tx->inTransaction());
$tx->rollBack();
$audit->failNext(false);
$service->sign($draft->prescription->id, 'doctor-1');
check('PDO: reintento firma el borrador original', $txRepository->findById($draft->prescription->id)->prescription->isSigned() && count($txRepository->forPatient('patient-1')) === 1);
finish();