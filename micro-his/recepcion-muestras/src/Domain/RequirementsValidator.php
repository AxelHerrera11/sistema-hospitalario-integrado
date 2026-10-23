<?php

declare(strict_types=1);

namespace Reception\Domain;

interface RequirementsValidator
{
    /** Same contract for every sample type: null means accepted; otherwise a controlled reason. */
    public function reason(array $observed, array $requirements, \DateTimeImmutable $now): ?string;
}
