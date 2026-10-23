<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/**
 * Evalúa coincidencias entre alergias activas del paciente y un medicamento.
 *
 * SD-PRE-02: la coincidencia se normaliza (trim, minúsculas, sin acentos)
 * entre allergen_code/allergen_name y active_ingredient/pharmacological_group.
 * El código del medicamento NO participa en la coincidencia.
 */
final class AllergyMatcher
{
    /** @param list<Allergy> $allergies */
    public function match(array $allergies, MedicationSnapshot $medication): array
    {
        $conflicts = [];

        $activeIngredientNorm = Allergy::normalize($medication->activeIngredient);
        $pharmacologicalGroupNorm = Allergy::normalize($medication->pharmacologicalGroup);

        foreach ($allergies as $allergy) {
            $allergenNorm = Allergy::normalize($allergy->label());

            if ($activeIngredientNorm !== '' && $allergenNorm !== '' && $allergenNorm === $activeIngredientNorm) {
                $conflicts[] = new AllergyConflict(
                    $allergy->label(),
                    $allergy->type,
                    $allergy->severity,
                    AllergyConflict::ACTIVE_INGREDIENT
                );
            }

            if ($pharmacologicalGroupNorm !== '' && $allergenNorm !== '' && $allergenNorm === $pharmacologicalGroupNorm) {
                $conflicts[] = new AllergyConflict(
                    $allergy->label(),
                    $allergy->type,
                    $allergy->severity,
                    AllergyConflict::PHARMACOLOGICAL_GROUP
                );
            }
        }

        return $conflicts;
    }
}
