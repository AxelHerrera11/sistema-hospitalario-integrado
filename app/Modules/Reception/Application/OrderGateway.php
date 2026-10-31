<?php

declare(strict_types=1);

namespace App\Modules\Reception\Application;

interface OrderGateway
{
    public function order(string $tenant, string $item): ?array;
}
