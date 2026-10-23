<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
/**
 * Router del micro-HIS en PHP puro (sin FastRoute ni PSR-15). Un middleware es un callable
 * fn(Request $request, callable $next): Response; un handler es fn(Request $request): Response.
 * No reemplaza el arranque de Laravel: solo lo usa el front controller del módulo.
 */
final class Router
{
    /** @var array<int, array{0: string, 1: string, 2: callable}> */
    private array $routes = [];
    /** @var callable[] */
    private array $middlewares = [];
    public function __construct(private AuthResponseTransformer $responses) {}
    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = [strtoupper($method), $regex, $handler];}
    public function pipe(callable $middleware): void
    {
        $this->middlewares[] = $middleware;}
    public function dispatch(Request $request): Response
    {
        // Las excepciones del handler se convierten aquí dentro para que los middleware vean el código real.
        $handler = fn(Request $r): Response => $this->safely(fn() => $this->route($r));
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $handler;
            $handler = fn(Request $r): Response => $middleware($r, $next);}
        return $this->safely(fn() => $handler($this->withJsonBody($request)));}
    private function safely(callable $action): Response
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->responses->error($e->getMessage(), 422);
        } catch (\DomainException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 400;
            return $this->responses->error($e->getMessage(), $status);}}
    private function route(Request $request): Response
    {
        $path = rtrim($request->getUri()->getPath(), '/') ?: '/';
        $allowed = [];
        foreach ($this->routes as [$method, $regex, $handler]) {
            if (!preg_match($regex, $path, $matches)) {
                continue;}
            if ($method !== $request->getMethod()) {
                $allowed[] = $method;
                continue;}
            foreach ($matches as $name => $value) {
                if (is_string($name)) {
                    $request = $request->withAttribute($name, $value);}}
            return $handler($request);}
        if ($allowed !== []) {
            return $this->responses->json(['message' => 'Método no permitido'], 405, ['Allow' => implode(', ', array_unique($allowed))]);}
        return $this->responses->error('Ruta no encontrada', 404);}
    private function withJsonBody(Request $request): Request
    {
        // ServerRequest::fromGlobals() deja $_POST (vacío) como parsedBody en peticiones JSON.
        if (!empty($request->getParsedBody()) || !str_contains($request->getHeaderLine('Content-Type'), 'json')) {
            return $request;}
        $raw = (string) $request->getBody();
        $data = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('El cuerpo debe ser JSON válido');}
        return $request->withParsedBody($data);}}
