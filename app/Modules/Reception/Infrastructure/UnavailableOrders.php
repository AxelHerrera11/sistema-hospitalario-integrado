<?php

declare(strict_types=1);

namespace App\Modules\Reception\Infrastructure;

use App\Modules\Reception\Application\OrderGateway;
use App\Modules\Reception\Domain\ReceptionError;

/** ASII-16 aún no publica el contrato de lab_order_items. */
final class UnavailableOrders implements OrderGateway
{
    public function order(string $tenant, string $item): ?array
    {
        throw new ReceptionError('LAB_ORDER_UNAVAILABLE', 'El proveedor de órdenes de laboratorio no está disponible.', 503);
    }
}
