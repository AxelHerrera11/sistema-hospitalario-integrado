<?php

declare(strict_types=1);

namespace App\GobernanzaAuditoria;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\Gobernanza\Presentation\AuditHttpHandler;

/**
 * GET /api/v1/audit-events. Solo traduce Request/Response de Laravel:
 * la identidad sale del guard "api" y del atributo "tenant" que fija
 * TenantMiddleware; la lógica vive en AuditHttpHandler y los casos de uso.
 */
final class AuditEventController
{
    public function __construct(private readonly AuditHttpHandler $handler) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $tenant = $request->attributes->get('tenant');
        $server = [
            'user_id' => $user?->getAuthIdentifier(),
            'tenant_id' => is_object($tenant) && method_exists($tenant, 'getKey') ? $tenant->getKey() : null,
        ];
        $headers = [
            'X-Tenant-ID' => $request->header('X-Tenant-ID'),
            'X-Request-ID' => $request->header('X-Request-ID'),
        ];

        $result = $this->handler->search($server, $headers, $request->query());

        return new JsonResponse($result['body'], $result['status'], $result['headers']);
    }
}
