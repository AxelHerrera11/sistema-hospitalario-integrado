<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Application;

/** UC-LAB-05 Consultar las pruebas vigentes a una fecha, con búsqueda por código o nombre. */
final class FindActiveLabTests
{
    public function __construct(private readonly LabTestStore $store) {}

    /** @return list<array<string, mixed>> */
    public function execute(string $date, string $search = ''): array
    {
        return $this->store->findActive($date, trim($search));
    }
}
