<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Domain;

final class PendingLabResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $patientCode,
        public readonly string $testCode,
        public readonly string $expectedUnit,
        public ResultStatus $status = ResultStatus::Pending,
        public ?string $value = null,
        public ?string $unit = null,
        public ?string $observation = null,
        public int $version = 1,
    ) {
    }

    public function capture(string $value, string $unit, ?string $observation): void
    {
        if ($this->status !== ResultStatus::Pending) {
            throw new DomainRuleViolation('Solo se capturan resultados pendientes.');
        }

        ResultRuleValidator::assertValueCanBeSaved($value, $unit, $this->expectedUnit);

        $this->value = $value;
        $this->unit = $unit;
        $this->observation = $observation;
        $this->status = ResultStatus::Captured;
    }

    public function correct(string $value, string $unit, string $reason): void
    {
        if ($this->status === ResultStatus::Pending) {
            throw new DomainRuleViolation('No se corrige un resultado que aun no fue capturado.');
        }

        if (trim($reason) === '') {
            throw new DomainRuleViolation('La correccion requiere motivo obligatorio.');
        }

        ResultRuleValidator::assertValueCanBeSaved($value, $unit, $this->expectedUnit);

        $this->value = $value;
        $this->unit = $unit;
        $this->status = ResultStatus::Corrected;
        $this->version++;
    }
}
