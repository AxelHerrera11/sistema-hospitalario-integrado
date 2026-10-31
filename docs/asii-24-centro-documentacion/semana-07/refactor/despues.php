<?php

declare(strict_types=1);
/* DESPUES: politica de visibilidad polimorfica; agregar un rol no exige editar el filtro. */

namespace Refactor\Documentacion;

interface PoliticaVisibilidad
{
    public function permite(array $documento): bool;
}

final class VeTodo implements PoliticaVisibilidad
{
    public function permite(array $documento): bool
    {
        return true;
    }
}

final class VeSusRoles implements PoliticaVisibilidad
{
    public function __construct(private readonly string $rol)
    {
    }

    public function permite(array $documento): bool
    {
        return in_array($this->rol, $documento['roles'], true);
    }
}

function politicaPara(string $rol): PoliticaVisibilidad
{
    return $rol === 'admin' ? new VeTodo() : new VeSusRoles($rol);
}

function listarDocumentosDespues(array $documentos, string $rol): array
{
    $politica = politicaPara($rol);
    $visibles = array_filter($documentos, static fn (array $d): bool => $politica->permite($d));
    return array_values(array_map(static fn (array $d): string => $d['titulo'], $visibles));
}
