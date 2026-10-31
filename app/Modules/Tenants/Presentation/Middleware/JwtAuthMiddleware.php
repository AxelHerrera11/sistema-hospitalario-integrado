<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Middleware;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
/**
 * Valida el Bearer y además que su sesión siga viva: revocar la sesión invalida al instante
 * todos los access tokens emitidos para ella, sin esperar a que expiren.
 */
final class JwtAuthMiddleware
{
    public function __construct(
        private TokenFactory $tokens,
        private SessionRepositoryInterface $sessions,
        private AuditLogRepositoryInterface $auditLog,
        private AuthResponseTransformer $responses,
        private array $publicPaths = ['/health', '/auth/login', '/auth/mfa', '/auth/refresh']
    ) {}
    public function __invoke(Request $request, callable $next): Response
    {
        if (in_array($request->getUri()->getPath(), $this->publicPaths, true)) {
            return $next($request);}
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Bearer ')) {
            return $this->responses->error('Token de autorización requerido', 401);}
        try {
            $claims = $this->tokens->validateAccessToken(substr($header, 7));
        } catch (\InvalidArgumentException) {
            return $this->responses->error('Token inválido o expirado', 401);}
        $session = $this->sessions->findById(new SessionId($claims->sessionId()));
        if ($session === null || $session->isRevoked() || $session->tenantId()->value() !== $claims->tenantId()) {
            return $this->responses->error('Sesión revocada', 401);}
        $tenantHeader = $request->getHeaderLine('X-Tenant-ID');
        if ($tenantHeader !== '' && $tenantHeader !== $claims->tenantId()) {
            $this->auditLog->log(new DomainEvent(DomainEvent::CROSS_TENANT_ATTEMPT, [
                'tenant_id' => $claims->tenantId(),
                'user_id' => $claims->subject(),
                'target_tenant_id' => $tenantHeader,
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',]));
            return $this->responses->error('El hospital indicado no coincide con el del token', 403);}
        return $next($request->withAttribute('auth', $claims));}}
