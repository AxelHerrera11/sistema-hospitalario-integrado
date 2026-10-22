<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\CatalogUnavailable;
use MicroHis\NotasSoap\Application\Icd10Catalog;

/** Catálogo CIE-10 en memoria con la misma regla que PdoIcd10Catalog: solo códigos activos. Con $down simula la caída. */
final class InMemoryIcd10Catalog implements Icd10Catalog
{
    /** @param array<string, array{0: string, 1: bool}> $codes código => [descripción, activo] */
    public function __construct(private readonly array $codes = [], private readonly bool $down = false)
    {
    }

    public function description(string $code): ?string
    {
        $this->down && throw new CatalogUnavailable('icd10_no_disponible');
        [$description, $active] = $this->codes[$code] ?? [null, false];
        return $active ? $description : null;
    }
}
