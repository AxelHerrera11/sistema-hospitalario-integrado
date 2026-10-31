<?php

declare(strict_types=1);

require_once __DIR__.'/harness.php';

use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\Exception\PrescriptionNotFound;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAllergyChecker;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAuditLogger;
use MicroHis\Prescripciones\Application\Persistence\InMemoryMedicationCatalog;
use MicroHis\Prescripciones\Application\PrescriptionRecord;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Application\PrescriptionService;
use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\NotTreatingPhysician;
use MicroHis\Prescripciones\Domain\PrescriptionNotDraft;
use MicroHis\Prescripciones\Domain\PrescriptionRuleViolation;
use MicroHis\Prescripciones\Domain\Severity;

final class ApplicationTestPrescriptionRepository implements PrescriptionRepository
{
    /** @var array<string, PrescriptionRecord> */
    public array $records = [];

    public bool $failWrites = false;

    public function transaction(callable $operation): mixed
    {
        $before = $this->records;
        try {
            return $operation();
        } catch (\Throwable $error) {
            $this->records = $before;
            throw $error;
        }
    }

    public function save(PrescriptionRecord $record): void
    {
        if ($this->failWrites) {
            throw new PersistenceFailure('write failed');
        }
        $this->records[$record->prescription->id] = $record;
    }

    public function findById(string $prescriptionId): ?PrescriptionRecord
    {
        return $this->records[$prescriptionId] ?? null;
    }

    public function forPatient(string $patientId): array
    {
        return array_values(array_filter($this->records, static fn (PrescriptionRecord $record): bool => $record->prescription->patientId === $patientId));
    }
}

function applicationFixture(array $allergies = []): array
{
    $repository = new ApplicationTestPrescriptionRepository;
    $checker = new InMemoryAllergyChecker($allergies);
    $catalog = new InMemoryMedicationCatalog([
        new MedicationSnapshot('med-1', 'AMX500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Penicilinas', true),
    ]);
    $audit = new InMemoryAuditLogger;
    return [new PrescriptionService($repository, $checker, $catalog, $audit), $repository, $checker, $catalog, $audit];
}

function applicationDraftInput(): array
{
    return [
        'patient_id' => 'patient-1',
        'doctor_profile_id' => 'doctor-1',
        'care_episode_id' => 'episode-1',
        'medication_id' => 'med-1',
        'dose_amount' => '500',
        'dose_unit' => 'mg',
        'route' => 'oral',
        'frequency_times' => 2,
        'frequency_interval' => 'day',
        'duration_value' => 7,
        'duration_unit' => 'days',
    ];
}
[$service, $repository, $checker, $catalog, $audit] = applicationFixture();
$created = $service->createDraft(applicationDraftInput());
check('servicio: crea un borrador draft y lo persiste con resultado clear', $created->prescription->isDraft() && $created->allergyCheckResult === 'clear' && count($repository->records) === 1);
check('servicio: genera un UUID y conserva cantidad y unidad separadas', preg_match('/^[0-9a-f-]{36}$/', $created->prescription->id) === 1 && $created->doseAmount === '500' && $created->doseUnit === 'mg');
check('servicio: conserva frecuencia y duración estructuradas', $created->frequencyTimes === 2 && $created->frequencyInterval === 'day' && $created->durationValue === 7 && $created->durationUnit === 'days');
check('servicio: registra el evento de creación con el identificador', $audit->logs[0]['event'] === 'prescription.draft_created' && $audit->logs[0]['payload']['prescription_id'] === $created->prescription->id);
[$service, $repository] = applicationFixture(['patient-1' => [new Allergy(null, 'Amoxicilina', AllergyType::Allergy, Severity::Mild)]]);
$warning = $service->createDraft(applicationDraftInput());
check('SD-PRE-01: mild permite crear borrador con warning', $warning->allergyCheckResult === 'warning' && $warning->warnings === ['ALLERGY_CONFLICT'] && count($repository->records) === 1);
foreach ([Severity::Moderate, Severity::Unknown, Severity::Severe, Severity::LifeThreatening] as $severity) {
    [$service, $repository] = applicationFixture(['patient-1' => [new Allergy(null, 'Amoxicilina', AllergyType::Allergy, $severity)]]);
    $blocked = thrown(static fn () => $service->createDraft(applicationDraftInput()), AllergyConflictBlocked::class);
    $allowed = in_array($severity, [Severity::Moderate, Severity::Unknown], true);
    check('SD-PRE-01: '.$severity->value.' bloquea con excepción='.($allowed ? 'true' : 'false').' sin guardar',
        $blocked instanceof AllergyConflictBlocked && $blocked->exceptionAllowed === $allowed && $repository->records === []);
}
[$service, $repository, $checker] = applicationFixture();
$checker->failNext(true);
check('servicio: si la consulta de alergias falla no guarda', thrown(static fn () => $service->createDraft(applicationDraftInput()), AllergyCheckUnavailable::class) instanceof AllergyCheckUnavailable && $repository->records === []);
[$service, $repository, , $catalog] = applicationFixture();
$catalog->upsert(new MedicationSnapshot('med-1', 'AMX500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Penicilinas', false));
check('servicio: medicamento inactivo no genera borrador', thrown(static fn () => $service->createDraft(applicationDraftInput()), MedicationNotFound::class) instanceof MedicationNotFound && $repository->records === []);
[$service, $repository] = applicationFixture();
$invalid = applicationDraftInput();
$invalid['dose_amount'] = '500 mg';
check('servicio: entrada inválida se rechaza antes de guardar', thrown(static fn () => $service->createDraft($invalid), PrescriptionRuleViolation::class) instanceof PrescriptionRuleViolation && $repository->records === []);
[$service, $repository] = applicationFixture();
$record = $service->createDraft(applicationDraftInput());
$listed = $service->forPatient('patient-1');
check('servicio: consulta y lista devuelven solo los registros del paciente', $service->get($record->prescription->id) === $record && $listed === [$record] && $service->forPatient('patient-2') === []);
check('servicio: identificador inexistente produce PrescriptionNotFound', thrown(static fn () => $service->get('missing'), PrescriptionNotFound::class) instanceof PrescriptionNotFound);
[$service, $repository, $checker] = applicationFixture();
$record = $service->createDraft(applicationDraftInput());
$checker->setAllergies('patient-1', [new Allergy(null, 'Amoxicilina', AllergyType::Allergy, Severity::Moderate)]);
$blockedAtSign = thrown(static fn () => $service->sign($record->prescription->id, 'doctor-1'), AllergyConflictBlocked::class);
check('SD-PRE-01: conflicto moderate al firmar bloquea con excepción y conserva draft',
    $blockedAtSign instanceof AllergyConflictBlocked && $blockedAtSign->exceptionAllowed && $repository->findById($record->prescription->id)?->prescription->isDraft());
[$service, $repository] = applicationFixture();
$record = $service->createDraft(applicationDraftInput());
$wrongSigner = thrown(static fn () => $service->sign($record->prescription->id, 'doctor-2'), NotTreatingPhysician::class);
check('servicio: otro médico no firma y conserva draft', $wrongSigner instanceof NotTreatingPhysician && $repository->findById($record->prescription->id)?->prescription->isDraft());
$signed = $service->sign($record->prescription->id, 'doctor-1');
$secondSignature = thrown(static fn () => $service->sign($record->prescription->id, 'doctor-1'), PrescriptionNotDraft::class);
check('servicio: médico tratante firma una vez y segunda firma no persiste', $signed->prescription->isSigned() && $repository->findById($record->prescription->id) === $signed && $secondSignature instanceof PrescriptionNotDraft && count($repository->records) === 1);
// Una auditoría fallida no deja borradores ni firmas persistidas.
[$service, $repository, $checker, $catalog, $audit] = applicationFixture();
$audit->failNext(true);
$failure = thrown(fn () => $service->createDraft(applicationDraftInput()), \MicroHis\Prescripciones\Application\Exception\AuditUnavailable::class);
check('auditoria: fallo al crear revierte el borrador', $failure !== null && $repository->records === [] && $audit->logs === []);
$audit->failNext(false);
$draft = $service->createDraft(applicationDraftInput());
$audit->failNext(true);
$failure = thrown(fn () => $service->sign($draft->prescription->id, 'doctor-1'), \MicroHis\Prescripciones\Application\Exception\AuditUnavailable::class);
check('auditoria: fallo al firmar conserva borrador y solo su evento', $failure !== null && $repository->findById($draft->prescription->id)->prescription->isDraft() && count($audit->logs) === 1);
$audit->failNext(false);
$service->sign($draft->prescription->id, 'doctor-1');
check('auditoria: reintento firma el mismo borrador', count($repository->records) === 1 && $repository->findById($draft->prescription->id)->prescription->isSigned() && count($audit->logs) === 2);