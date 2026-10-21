<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/**
 * Identidad ya autenticada que llega desde fuera (JWT + X-Tenant-ID en Laravel,
 * cuenta de servicio en CI). El módulo no autentica: solo autoriza y registra.
 */
final readonly class Actor
{
    public function __construct(
        public string $userId,
        public string $tenantId,
    ) {
        if ($userId === '' || $tenantId === '') {
            throw new \InvalidArgumentException('Actor incompleto: usuario y tenant son obligatorios.');
        }
    }
}
