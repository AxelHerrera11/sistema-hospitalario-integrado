<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

/** Catálogo CIE-10. Su origen real está por acordar; cambiarlo solo toca el adaptador. */
interface Icd10Catalog
{
    /**
     * @return string|null descripción del código vigente, o null si no existe
     * @throws CatalogUnavailable si el catálogo no responde
     */
    public function description(string $code): ?string;
}
