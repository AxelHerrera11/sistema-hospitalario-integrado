<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Application;

use MicroHIS\Documentacion\Domain\Documento;
use MicroHIS\Documentacion\Domain\DocumentoRepository;

/**
 * Caso de uso: publicar un documento (valida roles en el Dominio antes de persistir).
 */
final class PublicarDocumento
{
    public function __construct(private readonly DocumentoRepository $repositorio)
    {
    }

    /** @param list<string> $roles */
    public function ejecutar(string $titulo, array $roles): Documento
    {
        $documento = Documento::publicar('doc-' . bin2hex(random_bytes(4)), $titulo, $roles);
        $this->repositorio->guardar($documento);
        return $documento;
    }
}
