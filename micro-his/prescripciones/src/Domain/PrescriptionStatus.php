<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Estado del ciclo de vida de la prescripción: borrador y firmada (inmutable). */
enum PrescriptionStatus: string
{
    case Draft = 'draft';
    case Signed = 'signed';
}
