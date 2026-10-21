<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Middleware;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use ArrayObject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
/** Limita respuestas de error por IP en los endpoints de autenticación (ventana fija). */
final class RateLimitMiddleware
{
    /** @param ArrayObject<string, array{count: int, window_start: int}> $hits estado compartido del módulo */
    public function __construct(
        private ArrayObject $hits,
        private AuthResponseTransformer $responses,
        private int $maxAttempts = 20,
        private int $windowSeconds = 900,
        private array $limitedPaths = ['/auth/login', '/auth/mfa', '/auth/refresh']
    ) {}
    public function __invoke(Request $request, callable $next): Response
    {
        $path = $request->getUri()->getPath();
        if (!in_array($path, $this->limitedPaths, true)) {
            return $next($request);}
        $key = ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown') . '|' . $path;
        $now = time();
        $entry = $this->hits[$key] ?? ['count' => 0, 'window_start' => $now];
        if ($now - $entry['window_start'] >= $this->windowSeconds) {
            $entry = ['count' => 0, 'window_start' => $now];}
        if ($entry['count'] >= $this->maxAttempts) {
            $retryAfter = $this->windowSeconds - ($now - $entry['window_start']);
            return $this->responses->json(['message' => 'Demasiados intentos. Intente nuevamente más tarde.', 'retry_after' => $retryAfter], 429, ['Retry-After' => (string) $retryAfter]);}
        $response = $next($request);
        if ($response->getStatusCode() >= 400) {
            $entry['count']++;}
        $this->hits[$key] = $entry;
        return $response;}}
