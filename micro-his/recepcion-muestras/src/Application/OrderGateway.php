<?php

declare(strict_types=1);

namespace Reception\Application;

interface OrderGateway
{
    public function order(string $tenant, string $item): ?array;
}
