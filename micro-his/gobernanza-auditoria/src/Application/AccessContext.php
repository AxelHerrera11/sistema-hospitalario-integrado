<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

/**
 * Identidad resuelta por el servidor (JWT + middleware de tenant).
 * Nunca se construye con valores tomados libremente de la petición.
 */
final readonly class AccessContext
{
    public function __construct(
        public string $userId,
        public string $tenantId,
        public string $correlationId,
    ) {
        if ($userId === '' || $tenantId === '' || $correlationId === '') {
            throw new \InvalidArgumentException('access_context');
        }
    }
}
