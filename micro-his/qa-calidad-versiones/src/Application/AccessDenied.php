<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/** El actor está autenticado pero no tiene el permiso (HTTP 403). */
final class AccessDenied extends \RuntimeException
{
    public function __construct(public readonly string $permission)
    {
        parent::__construct("Permiso requerido: {$permission}");
    }
}
