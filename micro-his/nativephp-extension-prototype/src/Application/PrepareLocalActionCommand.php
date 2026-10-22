<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Application;

final class PrepareLocalActionCommand
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $actorId,
        public readonly string $kind,
        public readonly string $payload,
    ) {
    }
}
