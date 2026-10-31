<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/**
 * Conflicto de alergia que impide continuar: la respuesta es 422 ALLERGY_CONFLICT.
 * exceptionAllowed indica si el conflicto es no grave y admite excepción con permiso y justificación (SD-PRE-01).
 */
final class AllergyConflictBlocked extends \RuntimeException
{
    /** @param list<AllergyConflict> $conflicts */
    public function __construct(public readonly array $conflicts, public readonly bool $exceptionAllowed)
    {
        parent::__construct('La prescripción tiene un conflicto de alergia con el paciente.');
    }
}
