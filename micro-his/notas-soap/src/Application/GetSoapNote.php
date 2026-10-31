<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;

/** Lectura de una nota con sus secciones y diagnósticos activos. Además del permiso RBAC, exige asignación activa en el episodio: es dato clínico. Sin asignación responde igual que ante una nota inexistente, para no revelar qué identificadores existen. */
final class GetSoapNote
{
    public function __construct(private readonly TreatingPhysicianGuard $guard, private readonly ClinicalNoteRepository $notes)
    {
    }

    public function execute(string $userId, string $noteId): ClinicalNote
    {
        $note = $this->notes->find($noteId);
        try {
            $note === null || $this->guard->assertAllowed($userId, $note->episodeId);
        } catch (SoapRuleViolation) {
            $note = null;
        }
        return $note ?? throw new SoapRuleViolation('NOTE_NOT_FOUND', 404);
    }
}
