<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Application;

use MicroHis\SalasCamas\Domain\Bed;

interface BedRepository
{
    public function listByWardId(string $wardId): array;
    public function insertBed(Bed $bed): Bed;
}
