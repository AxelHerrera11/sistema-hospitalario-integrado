<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

/**
 * Puerto de autorización. La implementación real delega en el RBAC del
 * proyecto (Integration\RbacAccessGate -> MicroHis\Rbac\Application\Authorize).
 * Ante cualquier fallo debe denegar.
 */
interface AccessGate
{
    public function decide(string $userId, string $tenantId, string $permission): AccessDecision;
}
