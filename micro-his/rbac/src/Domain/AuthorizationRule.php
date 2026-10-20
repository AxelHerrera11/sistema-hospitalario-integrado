<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

interface AuthorizationRule
{
    /** Devuelve null si permite o el motivo de la denegación. */
    public function decide(array $context): ?string;
}
