<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Domain;

final class SyncPolicy
{
    public static function assertCanSync(ConnectivityStatus $connectivity): void
    {
        if ($connectivity !== ConnectivityStatus::Online) {
            throw new DomainRuleViolation('No se puede sincronizar en modo offline.');
        }
    }
}
