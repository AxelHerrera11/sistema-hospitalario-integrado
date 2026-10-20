<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

interface PermissionProvider
{
    public function permissionsFor(string $roleCode): array;
}
