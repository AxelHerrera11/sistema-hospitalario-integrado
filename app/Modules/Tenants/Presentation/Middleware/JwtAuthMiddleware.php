<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Middleware;
use App\Modules\Tenants\Domain\Services\JwtTokenService;
use App\Modules\Tenants\Domain\Services\BlocklistService;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
final class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtTokenService $jwtService,
        private BlocklistService $blocklist
    ) {}
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $publicRoutes = ['/auth/login', '/auth/register', '/auth/refresh', '/auth/mfa'];
        $path = $request->getUri()->getPath();
        if (in_array($path, $publicRoutes, true)) {
            return $handler->handle($request);
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Token de autorización requerido');
        }
        $token = substr($authHeader, 7);
        try {
            $claims = $this->jwtService->validateAccessToken($token);
            if ($this->blocklist->exists($claims->jti())) {
                return $this->unauthorized('Token revocado');
            }
            $request = $request->withAttribute('auth_claims', $claims->toArray());
            $request = $request->withAttribute('auth_user', [
                'user_id' => $claims->subject(),
                'roles' => $claims->roles(),
                'tenant_id' => $claims->tenantId(),
                'session_id' => $claims->sessionId(),
                'device_id' => $claims->deviceId(),
                'jti' => $claims->jti(),
            ]);
            $tenantHeader = $request->getHeaderLine('X-Tenant-ID');
            if ($tenantHeader !== '' && $tenantHeader !== $claims->tenantId()) {
                return $this->forbidden('El tenant indicado no coincide con el usuario del token');
            }
        } catch (\InvalidArgumentException $e) {
            return $this->unauthorized('Token inválido o expirado: ' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->unauthorized('Error de autenticación');
        }
        return $handler->handle($request);
    }
    private function unauthorized(string $message): Response
    {
        $response = new \Nyholm\Psr7\Response(401, ['Content-Type' => 'application/json']);
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => $message, 'code' => 401]
        ]));
        return $response;
    }
    private function forbidden(string $message): Response
    {
        $response = new \Nyholm\Psr7\Response(403, ['Content-Type' => 'application/json']);
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => $message, 'code' => 403]
        ]));
        return $response;
    }
}
