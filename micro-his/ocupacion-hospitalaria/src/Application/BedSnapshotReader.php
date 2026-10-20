<?php

declare(strict_types=1);

namespace MicroHis\Occupancy\Application;

use MicroHis\Occupancy\Domain\BedSnapshot;

interface BedSnapshotReader
{
    /** @return list<BedSnapshot> */
    public function readAt(\DateTimeImmutable $cutoff): array;
}
