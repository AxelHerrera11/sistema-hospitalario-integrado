<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Domain;

final class BiochemistReviewPolicy
{
    public static function assertCanDecide(string $role, ReviewableLabResult $result): void
    {
        if ($role !== 'bioquimico') {
            throw new DomainRuleViolation('Solo el bioquimico puede validar o rechazar resultados.');
        }

        if ($result->status !== ReviewStatus::PendingReview) {
            throw new DomainRuleViolation('El resultado ya tiene una decision registrada.');
        }
    }
}
