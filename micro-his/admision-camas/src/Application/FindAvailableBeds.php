<?php

declare(strict_types=1);

namespace MicroHis\Admision\Application;

/** UC-ADM-03 Buscar camas disponibles, opcionalmente por tipo. */
final class FindAvailableBeds
{
    public function __construct(private readonly AdmissionStore $store) {}

    /** @return list<array<string, mixed>> */
    public function execute(string $type = ''): array
    {
        return $this->store->availableBeds(trim($type));
    }
}
