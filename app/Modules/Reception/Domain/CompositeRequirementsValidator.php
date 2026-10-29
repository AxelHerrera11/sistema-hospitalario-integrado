<?php

declare(strict_types=1);

namespace App\Modules\Reception\Domain;

final class CompositeRequirementsValidator implements RequirementsValidator
{
    public function __construct(private array $validators) {}

    public function reason(array $observed, array $requirements, \DateTimeImmutable $now): ?string
    {
        foreach ($this->validators as $validator) {
            if ($reason = $validator->reason($observed, $requirements, $now)) {
                return $reason;
            }
        }

        return null;
    }
}
