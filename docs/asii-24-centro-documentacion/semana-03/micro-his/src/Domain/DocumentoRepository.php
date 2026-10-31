<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Domain;

/**
 * Puerto de persistencia de documentos. Definido en el Dominio (Inversion de Dependencias).
 */
interface DocumentoRepository
{
    /** @return list<Documento> */
    public function todos(): array;

    public function guardar(Documento $documento): void;

    public function buscar(string $id): ?Documento;
}
