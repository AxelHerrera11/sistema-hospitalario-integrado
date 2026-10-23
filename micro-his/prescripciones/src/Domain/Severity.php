<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Severidad de la alergia; replica los valores admitidos por la columna allergies.severity. */
enum Severity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';
    case LifeThreatening = 'life_threatening';
    case Unknown = 'unknown';
}
