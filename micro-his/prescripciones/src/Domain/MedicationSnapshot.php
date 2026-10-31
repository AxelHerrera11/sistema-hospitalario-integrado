<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Copia de los datos del medicamento que la prescripción necesita; se toma del catálogo, no de la tabla directa. */
final readonly class MedicationSnapshot
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $activeIngredient,
        public string $pharmacologicalGroup,
        public bool $active,
    ) {}
}
