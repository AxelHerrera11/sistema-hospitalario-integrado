<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Domain;

final class ReviewableLabResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $patientCode,
        public readonly string $testCode,
        public readonly string $value,
        public readonly string $unit,
        public readonly string $expectedUnit,
        public ReviewStatus $status = ReviewStatus::PendingReview,
        public ?string $decisionReason = null,
        public int $version = 1,
    ) {
    }

    public function validate(string $role): void
    {
        BiochemistReviewPolicy::assertCanDecide($role, $this);
        $this->assertUnitIsExpected();
        $this->status = ReviewStatus::Validated;
        $this->version++;
    }

    public function reject(string $role, string $reason): void
    {
        BiochemistReviewPolicy::assertCanDecide($role, $this);

        if (trim($reason) === '') {
            throw new DomainRuleViolation('El rechazo requiere motivo obligatorio.');
        }

        $this->status = ReviewStatus::Rejected;
        $this->decisionReason = $reason;
        $this->version++;
    }

    private function assertUnitIsExpected(): void
    {
        if ($this->unit !== $this->expectedUnit) {
            throw new DomainRuleViolation('La unidad del resultado no coincide con la unidad esperada.');
        }
    }
}
