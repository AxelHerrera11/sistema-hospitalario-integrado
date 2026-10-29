<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

use DateTimeImmutable;
use MicroHis\Prescripciones\Application\Exception\PrescriptionNotFound;
use MicroHis\Prescripciones\Domain\AllergyConflict;
use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\AllergyMatcher;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\Prescription;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use MicroHis\Prescripciones\Domain\PrescriptionRuleViolation;
use MicroHis\Prescripciones\Domain\Severity;

final class PrescriptionService
{
    public function __construct(
        private readonly PrescriptionRepository $prescriptions,
        private readonly AllergyChecker $allergies,
        private readonly MedicationCatalog $medications,
        private readonly AuditLogger $audit,
    ) {}

    public function createDraft(array $input): PrescriptionRecord
    {
        $patientId = $this->requiredString($input, 'patient_id');
        $doctorProfileId = $this->requiredString($input, 'doctor_profile_id');
        $careEpisodeId = $this->requiredString($input, 'care_episode_id');
        $medicationId = $this->requiredString($input, 'medication_id');
        $doseAmount = $this->positiveDecimal($input['dose_amount'] ?? null, 'dose_amount');
        $doseUnit = $this->requiredString($input, 'dose_unit');
        $frequencyTimes = $this->positiveInteger($input['frequency_times'] ?? null, 'frequency_times');
        $frequencyInterval = $this->optionalString($input, 'frequency_interval');
        $durationValue = $this->positiveInteger($input['duration_value'] ?? null, 'duration_value');
        $durationUnit = $this->requiredString($input, 'duration_unit');
        $route = PrescriptionRoute::fromInput($this->requiredString($input, 'route'));
        $medication = $this->medications->find($medicationId);
        $conflicts = $this->conflicts($patientId, $medication);
        [$checkResult, $warnings] = $this->allergyResult($conflicts);
        $prescription = Prescription::createDraft(
            $this->uuid(),
            $patientId,
            $doctorProfileId,
            $careEpisodeId,
            $medication,
            $route,
            $doseAmount.' '.$doseUnit,
            (string) $frequencyTimes,
            (string) $durationValue,
            $this->optionalString($input, 'notes'),
        );
        $record = new PrescriptionRecord(
            $prescription,
            $checkResult,
            $doseAmount,
            $doseUnit,
            $frequencyTimes,
            $frequencyInterval,
            $durationValue,
            $durationUnit,
            $warnings,
        );
        $this->prescriptions->transaction(function () use ($record, $prescription, $patientId, $checkResult): void {
            $this->prescriptions->save($record);
            $this->audit->record('prescription.draft_created', [
                'prescription_id' => $prescription->id,
                'patient_id' => $patientId,
                'allergy_check_result' => $checkResult,
            ]);
        });
        return $record;
    }
    /** @return list<PrescriptionRecord> */
    public function forPatient(string $patientId): array
    {
        return $this->prescriptions->forPatient($patientId);
    }

    public function get(string $prescriptionId): PrescriptionRecord
    {
        return $this->prescriptions->findById($prescriptionId)
            ?? throw new PrescriptionNotFound($prescriptionId);
    }

    public function sign(string $prescriptionId, string $signerDoctorProfileId): PrescriptionRecord
    {
        $record = $this->get($prescriptionId);
        $prescription = $record->prescription;
        $conflicts = $this->conflicts($prescription->patientId, $prescription->medication);
        [$checkResult, $warnings] = $this->allergyResult($conflicts);
        $signed = $prescription->sign($signerDoctorProfileId, new DateTimeImmutable, $conflicts);
        $signedRecord = $record->withPrescription($signed, $checkResult, $warnings);
        $this->prescriptions->transaction(function () use ($signedRecord, $prescriptionId): void {
            $this->prescriptions->save($signedRecord);
            $this->audit->record('prescription.signed', ['prescription_id' => $prescriptionId]);
        });
        return $signedRecord;
    }
    /** @return list<AllergyConflict> */
    private function conflicts(string $patientId, MedicationSnapshot $medication): array
    {
        return (new AllergyMatcher)->match($this->allergies->activeAllergies($patientId), $medication);
    }
    /** @param list<AllergyConflict> $conflicts
     * @return array{string, list<string>}
     */
    private function allergyResult(array $conflicts): array
    {
        foreach ($conflicts as $conflict) {
            if (in_array($conflict->severity, [Severity::Severe, Severity::LifeThreatening], true)) {
                throw new AllergyConflictBlocked($conflicts, false);
            }
        }
        foreach ($conflicts as $conflict) {
            if (in_array($conflict->severity, [Severity::Moderate, Severity::Unknown], true)) {
                throw new AllergyConflictBlocked($conflicts, true);
            }
        }
        return $conflicts === []
            ? ['clear', []]
            : ['warning', ['ALLERGY_CONFLICT']];
    }

    private function requiredString(array $input, string $field): string
    {
        $value = $input[$field] ?? null;
        if (! is_string($value) && ! is_int($value)) {
            throw new PrescriptionRuleViolation($field, 'El campo es obligatorio.');
        }
        $value = trim((string) $value);
        if ($value === '') {
            throw new PrescriptionRuleViolation($field, 'El campo es obligatorio.');
        }
        return $value;
    }

    private function optionalString(array $input, string $field): ?string
    {
        $value = $input[$field] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value)) {
            throw new PrescriptionRuleViolation($field, 'El campo debe ser texto.');
        }
        return trim($value);
    }

    private function positiveInteger(mixed $value, string $field): int
    {
        if ((! is_int($value) && ! is_string($value)) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0) {
            throw new PrescriptionRuleViolation($field, 'El campo debe ser un entero positivo.');
        }
        return (int) $value;
    }

    private function positiveDecimal(mixed $value, string $field): string
    {
        if ((! is_int($value) && ! is_float($value) && ! is_string($value)) || ! is_numeric($value)) {
            throw new PrescriptionRuleViolation($field, 'El campo debe ser un número positivo con hasta tres decimales.');
        }
        $normalized = trim((string) $value);
        $integerDigits = ltrim(explode('.', $normalized, 2)[0], '0');
        if (preg_match('/^\d+(?:\.\d{1,3})?$/', $normalized) !== 1 || (float) $normalized <= 0 || strlen($integerDigits) > 7) {
            throw new PrescriptionRuleViolation($field, 'El campo debe ser un número positivo con hasta tres decimales.');
        }
        return $normalized;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20);
    }
}