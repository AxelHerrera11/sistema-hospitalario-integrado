<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Middleware;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisRateLimitRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RedisRateLimitRepository $rateLimit,
        private int $maxAttempts = 20,
        private int $windowSeconds = 900
    ) {}
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $path = $request->getUri()->getPath();
        $authEndpoints = ['/auth/login', '/auth/register', '/auth/mfa'];
        if (!in_array($path, $authEndpoints, true)) {
            return $handler->handle($request);
        }
        $email = '';
        $data = $request->getParsedBody();
        if (is_array($data) && isset($data['email'])) {
            $email = $data['email'];
        }
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        if ($this->rateLimit->isRateLimited($email, $ip, $this->maxAttempts)) {
            $response = new \Nyholm\Psr7\Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => (string)$this->windowSeconds
            ]);
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => [
                    'message' => 'Demasiados intentos. Intente nuevamente más tarde.',
                    'code' => 429,
                    'retry_after' => $this->windowSeconds
                ]
            ]));
            return $response;
        }
        $response = $handler->handle($request);
        if ($response->getStatusCode() >= 400) {
            $this->rateLimit->incrementAttempts($email, $ip);
        }
        return $response;
    }
}
