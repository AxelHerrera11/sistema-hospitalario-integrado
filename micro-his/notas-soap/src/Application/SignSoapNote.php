<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\SignaturePolicy;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;

/** UC-SOAP-05: firma de la nota por su autor tratante. La nota firmada queda inmutable y la firma se audita en la misma transacción. */
final class SignSoapNote
{
    public function __construct(
        private readonly TreatingPhysicianGuard $guard,
        private readonly SignaturePolicy $policy,
        private readonly ClinicalNoteRepository $notes,
    ) {
    }

    public function execute(string $userId, string $noteId): void
    {
        $note = $this->notes->find($noteId) ?? throw new SoapRuleViolation('NOTE_NOT_FOUND', 404);
        $this->guard->assertAllowed($userId, $note->episodeId, $note->authorId);
        $this->policy->assertCanSign($note);
        $this->notes->sign($note);
    }
}
