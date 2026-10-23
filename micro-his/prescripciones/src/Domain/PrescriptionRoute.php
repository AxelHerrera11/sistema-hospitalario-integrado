<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Vía de administración de la prescripción; mismos seis valores que el CHECK de la tabla H5. */
enum PrescriptionRoute: string
{
    case Oral = 'oral';
    case Intravenosa = 'intravenosa';
    case Intramuscular = 'intramuscular';
    case Subcutanea = 'subcutanea';
    case Topica = 'topica';
    case Inhalada = 'inhalada';

    public static function fromInput(string $value): self
    {
        return self::tryFrom(mb_strtolower(trim($value)))
            ?? throw new PrescriptionRuleViolation('route', 'La vía solo admite oral, intravenosa, intramuscular, subcutanea, topica o inhalada.');
    }
}
