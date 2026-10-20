<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

/** Asignación de un rol a un usuario dentro de un hospital (tenant). */
final class Membership
{
    public function __construct(public readonly string $userId, public readonly string $hospitalId, public readonly string $roleCode)
    {
    }
}
