<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Nota clínica SOAP con sus diagnósticos activos. */
final readonly class ClinicalNote
{
    /** @param list<Diagnosis> $diagnoses */
    public function __construct(public string $id, public string $episodeId, public string $authorId, public string $status = 'draft', public array $diagnoses = [])
    {
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }
}
