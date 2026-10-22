<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

use MicroHis\Prescripciones\Domain\Allergy;

/**
 * Puerto de consulta de alergias activas del paciente.
 * Implementaciones: InMemory (tests), PDO (producción).
 */
interface AllergyChecker
{
    /**
     * Devuelve las alergias activas del paciente.
     *
     * @return list<Allergy>
     * @throws AllergyCheckUnavailable si el servicio de alergias no responde
     */
    public function activeAllergies(string $patientId): array;
}