<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/** Puerto de autorización. El adaptador real delega en el RBAC del proyecto. */
interface AccessGate
{
    /** @throws AccessUnavailable si el servicio de autorización no puede decidir */
    public function allows(Actor $actor, string $permission): bool;
}
