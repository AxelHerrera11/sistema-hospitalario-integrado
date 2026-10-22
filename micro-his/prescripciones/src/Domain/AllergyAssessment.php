<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/**
 * Decide si un conjunto de conflictos bloquea la prescripción o admite excepción.
 *
 * SD-PRE-01: un conflicto grave (severity severe/life_threatening y no intolerance)
 * bloquea SIN excepción. Los no graves admiten excepción con permiso y justificación.
 */
final class AllergyAssessment
{
    /**
     * @param list<AllergyConflict> $conflicts
     * @return array{blocked: bool, conflicts: list<AllergyConflict>, exceptionAllowed: bool}
     */
    public function assess(array $conflicts): array
    {
        $grave = array_filter($conflicts, static fn (AllergyConflict $c): bool => $c->isGrave());

        if (! empty($grave)) {
            return [
                'blocked' => true,
                'conflicts' => array_values($conflicts),
                'exceptionAllowed' => false,
            ];
        }

        return [
            'blocked' => ! empty($conflicts),
            'conflicts' => array_values($conflicts),
            'exceptionAllowed' => ! empty($conflicts),
        ];
    }
}