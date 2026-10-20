<?php

declare(strict_types=1);

namespace MicroHis\Occupancy\Presentation;

use MicroHis\Occupancy\Application\OccupancySummaryQuery;
use MicroHis\Occupancy\Application\PersistenceFailure;

final readonly class OccupancyController
{
    public function __construct(private OccupancySummaryQuery $query)
    {
    }

    /** @return array{status: int, body: array} */
    public function summary(?\DateTimeImmutable $cutoff = null): array
    {
        try {
            return ['status' => 200, 'body' => $this->query->execute($cutoff ?? new \DateTimeImmutable())];
        } catch (PersistenceFailure) {
            return ['status' => 503, 'body' => ['error' => 'OCCUPANCY_UNAVAILABLE']];
        }
    }
}
