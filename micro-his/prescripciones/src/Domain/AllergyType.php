<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Tipo de la alergia; replica los valores admitidos por la columna allergies.allergy_type. */
enum AllergyType: string
{
    case Allergy = 'allergy';
    case Intolerance = 'intolerance';
    case Unknown = 'unknown';
}
