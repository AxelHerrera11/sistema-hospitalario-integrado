<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\SoapSections;

/** UC-SOAP-01: registro de la nota SOAP en borrador (RF-SOAP-01 a RF-SOAP-03). */
final class RegisterSoapNote
{
    public function __construct(private readonly TreatingPhysicianGuard $guard, private readonly ClinicalNoteRepository $notes)
    {
    }

    public function execute(string $userId, string $episodeId, SoapSections $sections): string
    {
        $this->guard->assertAllowed($userId, $episodeId);
        return $this->notes->add($episodeId, $userId, $sections);
    }
}
