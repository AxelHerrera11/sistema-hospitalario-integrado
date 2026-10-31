<?php

declare(strict_types=1);

namespace MicroHis\CentroDocumentacion\Application;

use MicroHis\CentroDocumentacion\Domain\Documento;

/**
 * Caso de uso de lectura (ISP: no conoce la publicación). Un documento que el rol no puede ver se trata
 * igual que uno inexistente, para no revelar su existencia.
 */
final class ConsultarCatalogo
{
    /** @var array<string, Documento> */
    private array $documentos = [];

    /** @param list<array{id: string, titulo: string, tipo: string, version: string, ruta: string, roles: list<string>}> $catalogo */
    public function __construct(array $catalogo)
    {
        foreach ($catalogo as $datos) {
            $documento = Documento::desde($datos);
            $this->documentos[$documento->id] = $documento;
        }
    }

    /**
     * @param list<string> $rolesSesion
     * @return list<array{id: string, titulo: string, tipo: string, version: string}>
     */
    public function visibles(array $rolesSesion): array
    {
        $visibles = array_filter($this->documentos, static fn (Documento $d): bool => $d->visiblePara($rolesSesion));

        return array_values(array_map(static fn (Documento $d): array => $d->resumen(), $visibles));
    }

    /** @param list<string> $rolesSesion */
    public function documento(string $id, array $rolesSesion): ?Documento
    {
        $documento = $this->documentos[$id] ?? null;

        return $documento !== null && $documento->visiblePara($rolesSesion) ? $documento : null;
    }
}
