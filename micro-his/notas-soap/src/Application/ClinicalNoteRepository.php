<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapSections;

/** Puerto de persistencia de notas y diagnósticos. Cada escritura guarda también su auditoría, todo o nada. */
interface ClinicalNoteRepository
{
    public function find(string $noteId): ?ClinicalNote;

    /** Crea la nota SOAP en borrador (versión 1) y devuelve su id. */
    public function add(string $episodeId, string $authorId, SoapSections $sections): string;

    public function addDiagnosis(ClinicalNote $note, Diagnosis $diagnosis, string $description): void;
}
