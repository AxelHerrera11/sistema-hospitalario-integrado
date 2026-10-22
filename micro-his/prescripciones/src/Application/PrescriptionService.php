<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\AuditUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyAssessment;
use MicroHis\Prescripciones\Domain\AllergyConflict;
use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\AllergyMatcher;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\NotTreatingPhysician;
use MicroHis\Prescripciones\Domain\Prescription;
use MicroHis\Prescripciones\Domain\PrescriptionNotDraft;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use MicroHis\Prescripciones\Domain\PrescriptionRuleViolation;

/**
 * Caso de uso principal de prescripciones.
 *
 * Orquesta: catálogo → alergias → matcher → assessment → persistencia → auditoría.
 * Reglas de negocio (SD-PRE-01 a SD-PRE-05) se aplican aquí y en el agregado Prescription.
 */
final class PrescriptionService
{
    public function __construct(
        private readonly PrescriptionRepository $repository,
        private readonly MedicationCatalog $catalog,
        private readonly AllergyChecker $allergyChecker,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Crea un borrador de prescripción.
     *
     * Valida medicamento en catálogo, comprueba alergias activas.
     * Si hay conflicto grave → lanza AllergyConflictBlocked (exceptionAllowed=false).
     * Si hay conflicto no grave → bloquea (exceptionAllowed=true); la capa de presentación
     * debe gestionar la excepción con permiso RBAC.
     *
     * @throws MedicationNotFound
     * @throws AllergyCheckUnavailable
     * @throws PersistenceFailure
     * @throws AuditUnavailable
     * @throws AllergyConflictBlocked
     * @throws PrescriptionRuleViolation
     */
    public function create(
        string $id,
        string $patientId,
        string $doctorProfileId,
        string $careEpisodeId,
        string $medicationId,
        string $route,
        string $dose,
        string $frequency,
        string $durationDays,
        ?string $notes = null,
    ): Prescription {
        // 1. Validar medicamento en catálogo
        $medication = $this->catalog->find($medicationId);

        // 2. Consultar alergias activas
        try {
            $allergies = $this->allergyChecker->activeAllergies($patientId);
        } catch (\Throwable $e) {
            throw new AllergyCheckUnavailable('No se pudieron consultar las alergias.', $e);
        }

        // 3. Matcher + Assessment
        $conflicts = (new AllergyMatcher())->match($allergies, $medication);
        $assessment = (new AllergyAssessment())->assess($conflicts);

        if ($assessment['blocked']) {
            $this->audit->record('prescripcion.bloqueada', [
                'prescription_id' => $id,
                'patient_id' => $patientId,
                'medication_id' => $medicationId,
                'conflicts' => array_map(static fn (AllergyConflict $c): array => $c->toArray(), $conflicts),
                'exception_allowed' => $assessment['exceptionAllowed'],
            ]);
            throw new AllergyConflictBlocked($conflicts, $assessment['exceptionAllowed']);
        }

        // 4. Crear borrador
        $prescription = Prescription::createDraft(
            $id,
            $patientId,
            $doctorProfileId,
            $careEpisodeId,
            $medication,
            PrescriptionRoute::fromInput($route),
            $dose,
            $frequency,
            $durationDays,
            $notes,
        );

        // 5. Persistir
        try {
            $this->repository->save($prescription);
        } catch (\Throwable $e) {
            throw new PersistenceFailure('No se pudo guardar la prescripción.', $e);
        }

        // 6. Auditar creación
        try {
            $this->audit->record('prescripcion.creada', [
                'prescription_id' => $prescription->id,
                'patient_id' => $patientId,
                'doctor_profile_id' => $doctorProfileId,
                'care_episode_id' => $careEpisodeId,
                'medication_id' => $medicationId,
                'route' => $route,
                'dose' => $dose,
                'frequency' => $frequency,
                'duration_days' => $durationDays,
            ]);
        } catch (\Throwable $e) {
            throw new AuditUnavailable('No se pudo auditar la creación.', $e);
        }

        return $prescription;
    }

    /**
     * Firma una prescripción.
     *
     * Revalida alergias al firmar. Si falla la consulta de alergias o aparece
     * un conflicto no cubierto → no firma, lanza excepción, borrador queda draft.
     *
     * @throws PrescriptionNotDraft
     * @throws NotTreatingPhysician
     * @throws AllergyCheckUnavailable
     * @throws AllergyConflictBlocked
     * @throws AuditUnavailable
     * @throws PersistenceFailure
     */
    public function sign(
        string $prescriptionId,
        string $signerDoctorProfileId,
        \DateTimeImmutable $signedAt,
    ): Prescription {
        $prescription = $this->repository->findById($prescriptionId);
        if (! $prescription) {
            throw new PrescriptionNotDraft('Prescripción no encontrada.');
        }

        // Revalidar alergias al firmar (SD-PRE-05)
        try {
            $allergies = $this->allergyChecker->activeAllergies($prescription->patientId);
        } catch (\Throwable $e) {
            throw new AllergyCheckUnavailable('No se pudieron consultar las alergias al firmar.', $e);
        }
        $conflicts = (new AllergyMatcher())->match($allergies, $prescription->medication);
        $assessment = (new AllergyAssessment())->assess($conflicts);

        if ($assessment['blocked']) {
            $this->audit->record('prescripcion.firma_bloqueada', [
                'prescription_id' => $prescriptionId,
                'signer_doctor_profile_id' => $signerDoctorProfileId,
                'conflicts' => array_map(static fn (AllergyConflict $c): array => $c->toArray(), $conflicts),
                'exception_allowed' => $assessment['exceptionAllowed'],
            ]);
            throw new AllergyConflictBlocked($conflicts, $assessment['exceptionAllowed']);
        }

        // Firmar (el agregado valida estado draft y médico tratante)
        try {
            $signed = $prescription->sign($signerDoctorProfileId, $signedAt, $conflicts);
        } catch (PrescriptionNotDraft | NotTreatingPhysician $e) {
            throw $e;
        }

        // Persistir cambio de estado
        try {
            $this->repository->save($signed);
        } catch (\Throwable $e) {
            throw new PersistenceFailure('No se pudo guardar la firma.', $e);
        }

        // Auditar firma (falla cerrada: si falla auditoría, rollback implícito en app)
        try {
            $this->audit->record('prescripcion.firmada', [
                'prescription_id' => $signed->id,
                'signed_by' => $signerDoctorProfileId,
                'signed_at' => $signedAt->format(\DateTimeInterface::ATOM),
                'allergy_conflicts_at_sign' => $signed->allergyConflictsAtSign,
            ]);
        } catch (\Throwable $e) {
            throw new AuditUnavailable('No se pudo auditar la firma.', $e);
        }

        return $signed;
    }

    public function findById(string $id): ?Prescription
    {
        return $this->repository->findById($id);
    }

    /** @return list<Prescription> */
    public function listByPatient(string $patientId): array
    {
        return $this->repository->listByPatient($patientId);
    }
}