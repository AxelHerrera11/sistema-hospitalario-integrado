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

    /** Crea la versión n + 1 de una nota firmada, en borrador y con su motivo, y devuelve su id. Si ya fue corregida, lanza NOTE_ALREADY_CORRECTED (409). */
    public function correct(ClinicalNote $note, SoapSections $sections, string $reason): string;

    /** Firma el borrador y audita la firma, todo o nada. Si otra sesión ya la firmó, lanza NOTE_SIGNED (409). */
    public function sign(ClinicalNote $note): void;
}
