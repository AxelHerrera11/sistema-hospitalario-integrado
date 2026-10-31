<?php

declare(strict_types=1);

namespace App\QaCalidad;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\QaCalidad\QaModule;

/**
 * /api/v1/qa/runs/*. Solo traduce Request/Response de Laravel: la identidad sale del guard "api" y del atributo
 * "tenant" que fija TenantMiddleware; rutas, validación y reglas viven en QaHttpHandler y los casos de uso.
 */
final class QaRunController
{
    public function __construct(private readonly QaModule $module) {}

    public function handle(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $server = [
            'user_id' => $request->user('api')?->getAuthIdentifier(),
            'tenant_id' => is_object($tenant) && method_exists($tenant, 'getKey') ? $tenant->getKey() : null,
        ];
        $headers = [
            'X-Tenant-ID' => $request->header('X-Tenant-ID'),
            'X-Request-ID' => $request->header('X-Request-ID'),
        ];

        $result = $this->module->http->handle(
            $request->getMethod(),
            '/' . ltrim($request->path(), '/'),
            $server,
            $headers,
            $request->query(),
            (string) $request->getContent(),
        );

        return new JsonResponse($result['body'], $result['status'], $result['headers']);
    }
}
