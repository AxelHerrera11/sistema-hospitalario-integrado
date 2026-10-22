<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\NotTreatingPhysician;
use MicroHis\Prescripciones\Domain\PrescriptionNotDraft;

/**
 * Agregado raíz de la prescripción.
 *
 * Ciclo de vida: draft → signed (inmutable).
 * Solo el médico tratante del episodio activo puede crear y firmar.
 * Al firmar se revalida la ausencia de conflictos de alergia.
 */
final class Prescription
{
    private function __construct(
        public readonly string $id,
        public readonly string $patientId,
        public readonly string $doctorProfileId,
        public readonly string $careEpisodeId,
        public readonly MedicationSnapshot $medication,
        public readonly PrescriptionRoute $route,
        public readonly string $dose,
        public readonly string $frequency,
        public readonly string $durationDays,
        public readonly ?string $notes,
        public readonly PrescriptionStatus $status,
        public readonly ?string $signedBy,
        public readonly ?\DateTimeImmutable $signedAt,
        public readonly ?array $allergyConflictsAtSign,
    ) {}

    /**
     * Crea un borrador nuevo.
     *
     * @throws PrescriptionRuleViolation si algún campo no cumple las reglas
     */
    public static function createDraft(
        string $id,
        string $patientId,
        string $doctorProfileId,
        string $careEpisodeId,
        MedicationSnapshot $medication,
        PrescriptionRoute $route,
        string $dose,
        string $frequency,
        string $durationDays,
        ?string $notes = null,
    ): self {
        if (trim($dose) === '') {
            throw new PrescriptionRuleViolation('dose', 'La dosis es obligatoria.');
        }
        if (trim($frequency) === '') {
            throw new PrescriptionRuleViolation('frequency', 'La frecuencia es obligatoria.');
        }
        if (trim($durationDays) === '' || ! ctype_digit($durationDays) || (int) $durationDays <= 0) {
            throw new PrescriptionRuleViolation('durationDays', 'La duración en días debe ser un entero positivo.');
        }

        return new self(
            $id,
            $patientId,
            $doctorProfileId,
            $careEpisodeId,
            $medication,
            $route,
            trim($dose),
            trim($frequency),
            trim($durationDays),
            $notes !== null ? trim($notes) : null,
            PrescriptionStatus::Draft,
            null,
            null,
            null,
        );
    }

    /**
     * Firma la prescripción como médico tratante.
     *
     * Requiere que:
     * - esté en estado draft
     * - el firmante sea el mismo doctorProfileId (tratante)
     * - no haya conflictos de alergia no cubiertos (exceptionAllowed = false)
     *
     * @param list<AllergyConflict> $conflictsAtSign conflictos detectados al firmar
     * @return self nueva instancia firmada (inmutable)
     * @throws PrescriptionNotDraft si no está en draft
     * @throws NotTreatingPhysician si no es el médico tratante
     * @throws AllergyConflictBlocked si hay conflicto grave o no cubierto
     */
    public function sign(
        string $signerDoctorProfileId,
        \DateTimeImmutable $signedAt,
        array $conflictsAtSign,
    ): self {
        if ($this->status !== PrescriptionStatus::Draft) {
            throw new PrescriptionNotDraft();
        }
        if ($signerDoctorProfileId !== $this->doctorProfileId) {
            throw new NotTreatingPhysician();
        }

        $assessment = (new AllergyAssessment())->assess($conflictsAtSign);
        if ($assessment['blocked'] && ! $assessment['exceptionAllowed']) {
            throw new AllergyConflictBlocked($conflictsAtSign, false);
        }

        return new self(
            $this->id,
            $this->patientId,
            $this->doctorProfileId,
            $this->careEpisodeId,
            $this->medication,
            $this->route,
            $this->dose,
            $this->frequency,
            $this->durationDays,
            $this->notes,
            PrescriptionStatus::Signed,
            $signerDoctorProfileId,
            $signedAt,
            array_map(static fn (AllergyConflict $c): array => $c->toArray(), $conflictsAtSign),
        );
    }

    public function isDraft(): bool
    {
        return $this->status === PrescriptionStatus::Draft;
    }

    public function isSigned(): bool
    {
        return $this->status === PrescriptionStatus::Signed;
    }
}