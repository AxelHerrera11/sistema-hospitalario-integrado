<?php
declare(strict_types=1);
namespace MicroHis\QaRegresion\Domain;

interface CaseRepository
{
    public function save(ManualCase $case): void;
    public function find(string $id): ?ManualCase;
}
