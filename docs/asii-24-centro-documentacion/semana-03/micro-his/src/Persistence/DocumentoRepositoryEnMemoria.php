<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Persistence;

use MicroHIS\Documentacion\Domain\Documento;
use MicroHIS\Documentacion\Domain\DocumentoRepository;
use MicroHIS\Documentacion\Domain\ErrorPersistenciaException;

/**
 * Doble en memoria de DocumentoRepository para pruebas.
 */
final class DocumentoRepositoryEnMemoria implements DocumentoRepository
{
    /** @var array<string, Documento> */
    private array $documentos = [];

    public function todos(): array
    {
        return array_values($this->documentos);
    }

    public function guardar(Documento $documento): void
    {
        $this->documentos[$documento->id] = $documento;
    }

    public function buscar(string $id): ?Documento
    {
        return $this->documentos[$id] ?? null;
    }
}

/**
 * Doble que siempre falla, para verificar la traduccion del error de persistencia.
 */
final class DocumentoRepositoryQueFalla implements DocumentoRepository
{
    public function todos(): array
    {
        throw new ErrorPersistenciaException('fallo simulado de persistencia');
    }

    public function guardar(Documento $documento): void
    {
        throw new ErrorPersistenciaException('fallo simulado de persistencia');
    }

    public function buscar(string $id): ?Documento
    {
        throw new ErrorPersistenciaException('fallo simulado de persistencia');
    }
}
