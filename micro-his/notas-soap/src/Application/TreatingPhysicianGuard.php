<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\SoapRuleViolation;

/** Acceso de RF-SOAP-03: médico tratante activo y, si se indica, autor de la nota. */
final class TreatingPhysicianGuard
{
    public function __construct(private readonly CareAssignmentReader $assignments)
    {
    }

    public function assertAllowed(string $userId, string $episodeId, ?string $authorId = null): void
    {
        if (($authorId !== null && $authorId !== $userId) || !$this->assignments->isTreating($userId, $episodeId)) {
            throw new SoapRuleViolation('NOT_TREATING_PHYSICIAN', 403);
        }
    }
}
