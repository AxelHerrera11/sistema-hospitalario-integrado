<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

use MicroHis\Prescripciones\Domain\Prescription;

final readonly class PrescriptionRecord
{
    /** @param list<string> $warnings */
    public function __construct(
        public Prescription $prescription,
        public string $allergyCheckResult,
        public string $doseAmount,
        public string $doseUnit,
        public int $frequencyTimes,
        public ?string $frequencyInterval,
        public int $durationValue,
        public string $durationUnit,
        public array $warnings = [],
    ) {}

    public function withPrescription(Prescription $prescription, string $allergyCheckResult, array $warnings = []): self
    {
        return new self(
            $prescription,
            $allergyCheckResult,
            $this->doseAmount,
            $this->doseUnit,
            $this->frequencyTimes,
            $this->frequencyInterval,
            $this->durationValue,
            $this->durationUnit,
            $warnings,
        );
    }
}
