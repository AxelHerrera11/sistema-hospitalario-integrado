<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Application;

use MicroHis\Alergias\Domain\AllergyAlert;

/** UC-ALG-05 Alerta visual para el expediente y Prescripciones (ASII-10 y ASII-15). */
final class GetAllergyAlert
{
    public function __construct(private readonly AllergyRepository $store) {}

    public function execute(string $patientId): AllergyAlert
    {
        try {
            return AllergyAlert::from($this->store->activeFor($patientId));
        } catch (PersistenceFailure) {
            return AllergyAlert::unavailable(); // falla segura: nunca «sin alergias» si no se pudo leer
        }
    }
}
