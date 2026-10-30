<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;

/** Lectura de una nota con sus secciones y diagnósticos activos. Además del permiso RBAC, exige asignación activa en el episodio: es dato clínico. */
final class GetSoapNote
{
    public function __construct(private readonly TreatingPhysicianGuard $guard, private readonly ClinicalNoteRepository $notes)
    {
    }

    public function execute(string $userId, string $noteId): ClinicalNote
    {
        $note = $this->notes->find($noteId) ?? throw new SoapRuleViolation('NOTE_NOT_FOUND', 404);
        $this->guard->assertAllowed($userId, $note->episodeId);
        return $note;
    }
}
