<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Application;

use MicroHIS\Documentacion\Domain\DocumentoRepository;

// Caso de uso: listar el catalogo visible para un rol.
final class ConsultarDocumentos
{
    public function __construct(private readonly DocumentoRepository $repositorio)
    {
    }

    public function listar(string $rol): array
    {
        $visibles = array_filter($this->repositorio->todos(), static fn ($d): bool => $d->visiblePara($rol));
        return array_values(array_map(
            static fn ($d): array => ['id' => $d->id, 'titulo' => $d->titulo, 'roles' => $d->roles],
            $visibles
        ));
    }
}
