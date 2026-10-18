<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

/** Resuelve los roles de un usuario dentro de un hospital (tenant). Separa la autorización de la persistencia. */
interface RoleResolver
{
    /** @return string[] roles del usuario en el hospital indicado */
    public function rolesIn(string $userId, string $hospitalId): array;
}
