<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Middleware;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
/** Valida X-Tenant-ID cuando viene y lo exige en las rutas que lo necesitan (login). */
final class TenantMiddleware
{
    public function __construct(
        private AuthResponseTransformer $responses,
        private array $requiredPaths = ['/auth/login']
    ) {}
    public function __invoke(Request $request, callable $next): Response
    {
        $tenantId = $request->getHeaderLine('X-Tenant-ID');
        if ($tenantId === '') {
            return in_array($request->getUri()->getPath(), $this->requiredPaths, true)
                ? $this->responses->error('La cabecera X-Tenant-ID es obligatoria', 400)
                : $next($request);}
        if (!Uuid::isValid($tenantId)) {
            return $this->responses->error('X-Tenant-ID debe ser un UUID válido', 400);}
        return $next($request->withAttribute('tenant_id', $tenantId));}}
