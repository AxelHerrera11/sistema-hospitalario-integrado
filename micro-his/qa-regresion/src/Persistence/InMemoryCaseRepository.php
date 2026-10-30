<?php
declare(strict_types=1);
namespace MicroHis\QaRegresion\Persistence;

use MicroHis\QaRegresion\Domain\{CaseRepository, ManualCase, StorageFailure};

final class InMemoryCaseRepository implements CaseRepository
{
    private array $cases = [];
    public function save(ManualCase $case): void
    {
        if (isset($this->cases[$case->id])) {
            throw new StorageFailure('No se pudo guardar el caso.');
        }
        $this->cases[$case->id] = $case;
    }
    public function find(string $id): ?ManualCase { return $this->cases[$id] ?? null; }
}
