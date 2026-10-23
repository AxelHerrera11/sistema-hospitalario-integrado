<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Application;

use MicroHis\SalasCamas\Domain\Ward;

interface WardRepository
{
    public function listAll(): array;
    public function findByCode(string $code): ?Ward;
    public function insertWard(Ward $ward): Ward;
}
