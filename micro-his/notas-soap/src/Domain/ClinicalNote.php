<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Nota clínica SOAP con sus diagnósticos activos. */
final readonly class ClinicalNote
{
    /** @param list<Diagnosis> $diagnoses */
    public function __construct(public string $id, public string $episodeId, public string $authorId, public string $status = 'draft', public array $diagnoses = [], public ?SoapSections $sections = null, public int $versionNo = 1, public ?string $supersedesId = null)
    {
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }
}
