<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Persistence;

use MicroHis\Prescripciones\Application\AllergyChecker;
use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Domain\Allergy;

/** Implementación InMemory para tests. */
final class InMemoryAllergyChecker implements AllergyChecker
{
    /** @var array<string, list<Allergy>> */
    private array $allergies = [];

    private bool $shouldFail = false;

    public function __construct(array $initial = [])
    {
        foreach ($initial as $patientId => $list) {
            $this->allergies[$patientId] = $list;
        }
    }

    /** @return list<Allergy> */
    public function activeAllergies(string $patientId): array
    {
        if ($this->shouldFail) {
            throw new AllergyCheckUnavailable;
        }

        return $this->allergies[$patientId] ?? [];
    }

    /** Para tests: establece alergias de un paciente. */
    public function setAllergies(string $patientId, array $list): void
    {
        $this->allergies[$patientId] = $list;
    }

    /** Para tests: simula fallo del servicio. */
    public function failNext(bool $fail): void
    {
        $this->shouldFail = $fail;
    }

    /** Para tests: limpia estado. */
    public function clear(): void
    {
        $this->allergies = [];
        $this->shouldFail = false;
    }
}
