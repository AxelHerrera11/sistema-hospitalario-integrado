<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Middleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
final class TenantMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $tenantId = $request->getHeaderLine('X-Tenant-ID');
        if ($tenantId === '') {
            $response = new \Nyholm\Psr7\Response(400, ['Content-Type' => 'application/json']);
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => ['message' => 'La cabecera X-Tenant-ID es obligatoria', 'code' => 400]
            ]));
            return $response;}
        if (!\Ramsey\Uuid\Uuid::isValid($tenantId)) {
            $response = new \Nyholm\Psr7\Response(400, ['Content-Type' => 'application/json']);
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => ['message' => 'X-Tenant-ID debe ser un UUID válido', 'code' => 400]
            ]));
            return $response;
        }
        $request = $request->withAttribute('tenant_id', $tenantId);
        return $handler->handle($request);
    }
}
