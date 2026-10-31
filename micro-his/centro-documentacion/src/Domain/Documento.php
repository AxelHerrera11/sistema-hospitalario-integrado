<?php

declare(strict_types=1);

namespace MicroHis\CentroDocumentacion\Domain;

use InvalidArgumentException;

/**
 * Documento publicado en el centro de documentación. Siempre tiene título, una ruta relativa dentro de
 * `docs/` y al menos un rol destinatario (regla de las semanas 3-4, ahora con la ruta validada).
 */
final readonly class Documento
{
    /** @param list<string> $roles */
    private function __construct(
        public string $id,
        public string $titulo,
        public string $tipo,
        public string $version,
        public string $ruta,
        public array $roles,
    ) {
    }

    /** @param array{id: string, titulo: string, tipo: string, version: string, ruta: string, roles: list<string>} $datos */
    public static function desde(array $datos): self
    {
        $roles = array_values(array_unique(array_filter($datos['roles'], static fn (string $r): bool => $r !== '')));
        if (trim($datos['titulo']) === '' || $roles === []) {
            throw new InvalidArgumentException("Documento {$datos['id']}: requiere titulo y al menos un rol.");
        }
        if (!preg_match('#^docs/[A-Za-z0-9._/-]+\.(md|yaml)$#', $datos['ruta']) || str_contains($datos['ruta'], '..')) {
            throw new InvalidArgumentException("Documento {$datos['id']}: la ruta debe estar dentro de docs/.");
        }

        return new self($datos['id'], $datos['titulo'], $datos['tipo'], $datos['version'], $datos['ruta'], $roles);
    }

    /** @param list<string> $rolesSesion roles que el servidor resolvió para la sesión, nunca los del cliente */
    public function visiblePara(array $rolesSesion): bool
    {
        return array_intersect($this->roles, $rolesSesion) !== [];
    }

    /** @return array{id: string, titulo: string, tipo: string, version: string} */
    public function resumen(): array
    {
        return ['id' => $this->id, 'titulo' => $this->titulo, 'tipo' => $this->tipo, 'version' => $this->version];
    }
}
