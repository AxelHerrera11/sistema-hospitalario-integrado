<?php

declare(strict_types=1);

namespace MicroHis\Occupancy\Application;

use MicroHis\Occupancy\Domain\OccupancyCalculator;

final readonly class ConsultOccupancy implements OccupancySummaryQuery
{
    public function __construct(
        private BedSnapshotReader $reader,
        private OccupancyCalculator $calculator,
    ) {
    }

    public function execute(\DateTimeImmutable $cutoff): array
    {
        return $this->calculator->calculate($this->reader->readAt($cutoff), $cutoff);
    }
}
