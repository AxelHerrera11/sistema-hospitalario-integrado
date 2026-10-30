<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Presentation;

use MicroHIS\Documentacion\Application\ConsultarDocumentos;
use MicroHIS\Documentacion\Application\PublicarDocumento;
use MicroHIS\Documentacion\Domain\DocumentoSinRolesException;

// Controlador MVC. No contiene SQL ni reglas: orquesta casos de uso y traduce errores a HTTP.
final class DocumentoController
{
    public function __construct(
        private readonly PublicarDocumento $publicar,
        private readonly ConsultarDocumentos $consultar
    ) {
    }

    public function publicar(array $entrada): array
    {
        try {
            $documento = $this->publicar->ejecutar((string) ($entrada['titulo'] ?? ''), (array) ($entrada['roles'] ?? []));
        } catch (DocumentoSinRolesException $e) {
            return ['status' => (int) substr($e->getMessage(), 0, 3), 'body' => ['error' => substr($e->getMessage(), 4)]];
        }
        return ['status' => 201, 'body' => ['id' => $documento->id]];
    }

    public function listar(array $entrada): array
    {
        $rol = (string) ($entrada['rol'] ?? '');
        return ['status' => 200, 'body' => ['documentos' => $this->consultar->listar($rol)]];
    }
}
