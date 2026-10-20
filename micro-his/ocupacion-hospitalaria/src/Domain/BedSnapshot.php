<?php

declare(strict_types=1);

namespace MicroHis\Occupancy\Domain;

final readonly class BedSnapshot
{
    public function __construct(
        public string $bedId,
        public string $wardId,
        public string $wardCode,
        public string $operationalStatus,
        public bool $hasActiveStay,
    ) {
        if (!in_array($operationalStatus, ['available', 'occupied', 'maintenance', 'inactive'], true)) {
            throw new \InvalidArgumentException('Unsupported bed status.');
        }
    }
}
