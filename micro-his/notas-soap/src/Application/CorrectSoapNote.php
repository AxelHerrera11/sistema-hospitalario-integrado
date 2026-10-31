<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\CorrectionPolicy;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;

/** UC-SOAP-06: corrige una nota firmada creando la versión n + 1 en borrador, que luego se completa y se firma. La firmada no se toca. */
final class CorrectSoapNote
{
    public function __construct(
        private readonly TreatingPhysicianGuard $guard,
        private readonly CorrectionPolicy $policy,
        private readonly ClinicalNoteRepository $notes,
    ) {
    }

    /** @return string id de la nueva versión */
    public function execute(string $userId, string $noteId, SoapSections $sections, string $reason): string
    {
        $note = $this->notes->find($noteId) ?? throw new SoapRuleViolation('NOTE_NOT_FOUND', 404);
        $this->guard->assertAllowed($userId, $note->episodeId, $note->authorId);
        $this->policy->assertCanCorrect($note, $reason);
        return $this->notes->correct($note, $sections, $reason);
    }
}
