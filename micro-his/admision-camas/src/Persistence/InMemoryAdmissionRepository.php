<?php

declare(strict_types=1);

namespace MicroHis\Admision\Persistence;

use MicroHis\Admision\Application\AdmissionRepository;
use MicroHis\Admision\Application\PersistenceFailure;
use MicroHis\Admision\Domain\Admission;

/** Adaptador en memoria: mismo contrato que PdoAdmissionRepository, para pruebas y para trabajar sin base. */
final class InMemoryAdmissionRepository implements AdmissionRepository
{
    /** @var array<string, Admission> estancias abiertas por id */
    public array $admissions = [];
    /** @var list<array{0: string, 1: string, 2: string}> evento, actor, id */
    public array $audit = [];
    public bool $failWrites = false; // simula una base caída

    /** @param list<string> $patients @param array<string, array{bed_id: string, code: string, type: string, status: string}> $beds */
    public function __construct(private readonly array $patients, public array $beds) {}

    public function patientExists(string $patientId): bool
    {
        return in_array($patientId, $this->patients, true);
    }

    public function hasOpenInpatientEpisode(string $patientId): bool
    {
        return in_array($patientId, array_map(static fn (Admission $a): string => $a->patientId, $this->admissions), true);
    }

    public function bed(string $bedId): ?array
    {
        $bed = $this->beds[$bedId] ?? null;

        return $bed === null ? null : ['bed_id' => $bed['bed_id'], 'type' => $bed['type'], 'status' => $bed['status']];
    }

    public function save(Admission $admission, string $actor): void
    {
        // Mismas garantías que los índices únicos parciales y el UPDATE … WHERE status = 'available'.
        if ($this->failWrites || ($this->beds[$admission->bedId]['status'] ?? null) !== 'available') {
            throw new PersistenceFailure(!$this->failWrites);
        }
        $this->admissions[$admission->stayId] = $admission;
        $this->beds[$admission->bedId]['status'] = 'occupied';
        $this->audit[] = ['admision.cama_asignada', $actor, $admission->stayId];
    }

    public function availableBeds(string $type): array
    {
        $found = array_filter($this->beds, static fn (array $b): bool => $b['status'] === 'available' && ($type === '' || $b['type'] === $type));
        usort($found, static fn (array $a, array $b): int => $a['code'] <=> $b['code']);

        return array_map(static fn (array $b): array => ['bed_id' => $b['bed_id'], 'code' => $b['code'], 'type' => $b['type']], $found);
    }
}
