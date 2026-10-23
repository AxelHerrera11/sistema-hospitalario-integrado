<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Persistence;

use MicroHis\Alergias\Application\AllergyRepository;
use MicroHis\Alergias\Application\PersistenceFailure;
use MicroHis\Alergias\Domain\Allergy;

/** Adaptador en memoria: mismo contrato que PdoAllergyRepository, para pruebas y para trabajar sin base. */
final class InMemoryAllergyRepository implements AllergyRepository
{
    /** @var array<string, Allergy> */
    public array $rows = [];
    /** @var list<array{0: string, 1: string, 2: string}> evento, actor, id */
    public array $audit = [];
    public bool $failWrites = false; // simulan una base caída
    public bool $failReads = false;

    /** @param list<string> $patients */
    public function __construct(private readonly array $patients) {}

    public function patientExists(string $patientId): bool
    {
        return in_array($patientId, $this->patients, true);
    }

    public function save(Allergy $allergy, string $actor): void
    {
        // Mismo criterio que el índice único parcial allergies_active_equivalent_unique.
        $key = static fn (Allergy $a): string => "{$a->patientId}|{$a->type}|" . mb_strtolower($a->allergenCode ?? $a->allergenName ?? '');
        if ($this->failWrites || in_array($key($allergy), array_map($key, $this->rows), true)) {
            throw new PersistenceFailure(!$this->failWrites);
        }
        $this->rows[$allergy->id] = $allergy;
        $this->audit[] = ['alergia.registrada', $actor, $allergy->id];
    }

    public function activeFor(string $patientId): array
    {
        if ($this->failReads) {
            throw new PersistenceFailure(false);
        }
        $found = array_values(array_filter($this->rows, static fn (Allergy $a): bool => $a->patientId === $patientId));

        return array_map(static fn (Allergy $a): array => ['allergy_id' => $a->id, 'allergen_code' => $a->allergenCode, 'allergen_name' => $a->allergenName,
            'reaction' => $a->reaction, 'allergy_type' => $a->type, 'severity' => $a->severity], $found);
    }
}
