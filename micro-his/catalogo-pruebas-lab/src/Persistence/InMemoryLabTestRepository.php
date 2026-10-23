<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Persistence;

use MicroHis\CatalogoLab\Application\LabTestRepository;
use MicroHis\CatalogoLab\Application\PersistenceFailure;
use MicroHis\CatalogoLab\Domain\LabTest;

/** Adaptador en memoria: mismo contrato que PdoLabTestRepository, para pruebas y para trabajar sin base. */
final class InMemoryLabTestRepository implements LabTestRepository
{
    /** @var array<string, LabTest> */
    public array $rows = [];
    /** @var list<array{0: string, 1: string, 2: string}> evento, actor, id */
    public array $audit = [];
    public bool $failNextSave = false; // simula una base caída

    public function latestVersionNo(string $code): int
    {
        return max([0, ...array_map(static fn (LabTest $t): int => $t->versionNo, $this->byCode($code))]);
    }

    public function hasActiveOverlap(string $code, string $validFrom): bool
    {
        return $this->byCode($code) !== []; // aún no hay cierre de vigencia: toda versión sigue abierta
    }

    public function save(LabTest $test, string $actor): void
    {
        if ($this->failNextSave || in_array($test->versionNo, array_map(static fn (LabTest $t): int => $t->versionNo, $this->byCode($test->code)), true)) {
            throw new PersistenceFailure(!$this->failNextSave);
        }
        $this->rows[$test->id] = $test;
        $this->audit[] = ['prueba_lab.registrada', $actor, $test->id];
    }

    public function findActive(string $date, string $search): array
    {
        $found = array_filter($this->rows, static fn (LabTest $t): bool => $t->validFrom <= $date
            && ($search === '' || $t->code === strtoupper($search) || mb_stripos($t->name, $search) !== false));
        usort($found, static fn (LabTest $a, LabTest $b): int => $a->code <=> $b->code);

        return array_map(static fn (LabTest $t): array => ['code' => $t->code, 'name' => $t->name, 'specimen_type' => $t->specimenType,
            'result_type' => $t->resultType, 'unit' => $t->unit, 'reference_low' => $t->referenceLow, 'reference_high' => $t->referenceHigh,
            'version_no' => $t->versionNo, 'valid_from' => $t->validFrom], $found);
    }

    /** @return list<LabTest> */
    private function byCode(string $code): array
    {
        return array_values(array_filter($this->rows, static fn (LabTest $t): bool => $t->code === $code));
    }
}
