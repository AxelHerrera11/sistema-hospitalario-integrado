<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Domain;

final class DocumentoSinRolesException extends \RuntimeException
{
}

final class ErrorPersistenciaException extends \RuntimeException
{
}

/**
 * Entidad Documento. Regla central: un documento publicado siempre tiene al menos un rol.
 */
final class Documento
{
    /**
     * @param list<string> $roles
     */
    private function __construct(
        public readonly string $id,
        public readonly string $titulo,
        public readonly array $roles
    ) {
    }

    /**
     * @param list<string> $roles
     */
    public static function publicar(string $id, string $titulo, array $roles): self
    {
        $roles = array_values(array_unique(array_filter($roles, static fn (string $r): bool => $r !== '')));
        if ($roles === []) {
            throw new DocumentoSinRolesException('422 documento_sin_roles');
        }
        return new self($id, $titulo, $roles);
    }

    public function visiblePara(string $rol): bool
    {
        return in_array($rol, $this->roles, true);
    }
}
