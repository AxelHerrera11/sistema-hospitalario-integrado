<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Application;

use MicroHIS\Documentacion\Domain\DocumentoRepository;

/**
 * Caso de uso: consultar el catalogo filtrado por rol del usuario.
 */
final class ConsultarDocumentos
{
    public function __construct(private readonly DocumentoRepository $repositorio)
    {
    }

    /** @return list<array{id:string,titulo:string,roles:list<string>}> */
    public function listar(string $rol): array
    {
        $visibles = array_filter(
            $this->repositorio->todos(),
            static fn ($d): bool => $d->visiblePara($rol)
        );
        return array_values(array_map(
            static fn ($d): array => ['id' => $d->id, 'titulo' => $d->titulo, 'roles' => $d->roles],
            $visibles
        ));
    }
}
