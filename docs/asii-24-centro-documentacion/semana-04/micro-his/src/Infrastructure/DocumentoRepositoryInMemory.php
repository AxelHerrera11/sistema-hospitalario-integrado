<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Infrastructure;

use MicroHIS\Documentacion\Domain\Documento;
use MicroHIS\Documentacion\Domain\DocumentoRepository;

// Adaptador Repository en memoria (pruebas y demos sin base de datos).
final class DocumentoRepositoryInMemory implements DocumentoRepository
{
    private array $documentos = [];

    public function todos(): array
    {
        return array_values($this->documentos);
    }

    public function guardar(Documento $documento): void
    {
        $this->documentos[$documento->id] = $documento;
    }
}
