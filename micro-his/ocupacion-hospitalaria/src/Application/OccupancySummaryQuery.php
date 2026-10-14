<?php

declare(strict_types=1);

namespace MicroHis\Occupancy\Application;

interface OccupancySummaryQuery
{
    public function execute(\DateTimeImmutable $cutoff): array;
}
