<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use FastRoute\RouteParser\Std;
use FastRoute\DataGenerator\GroupCountBased;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
final class Router
{
    private Dispatcher $dispatcher;
    private array $middlewares = [];
    public function __construct()
    {
        $this->dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
            $this->registerRoutes($r);});}
    public function addMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;}
    public function dispatch(Request $request): Response
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $request = $request->withAttributes($vars);
                $chain = new MiddlewareChain($this->middlewares, $handler);
                return $chain->handle($request);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $response = new \Nyholm\Psr7\Response(405, [
                    'Content-Type' => 'application/json',
                    'Allow' => implode(', ', $allowedMethods)]);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => ['message' => 'Método no permitido', 'code' => 405]]));
                return $response;
            default:
                $response = new \Nyholm\Psr7\Response(404, ['Content-Type' => 'application/json']);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => ['message' => 'Ruta no encontrada', 'code' => 404]]));
                return $response;}}
    private function registerRoutes(RouteCollector $r): void
    {
        $r->addRoute('POST', '/auth/register', [\App\Modules\Tenants\Presentation\Controllers\AuthController::class, 'register']);
        $r->addRoute('POST', '/auth/login', [\App\Modules\Tenants\Presentation\Controllers\AuthController::class, 'login']);
        $r->addRoute('POST', '/auth/mfa', [\App\Modules\Tenants\Presentation\Controllers\AuthController::class, 'verifyMfa']);
        $r->addRoute('POST', '/auth/refresh', [\App\Modules\Tenants\Presentation\Controllers\AuthController::class, 'refresh']);
        $r->addRoute('POST', '/auth/logout', [\App\Modules\Tenants\Presentation\Controllers\AuthController::class, 'revoke']);
        $r->addRoute('GET', '/admin/users', [\App\Modules\Tenants\Presentation\Controllers\AdminUserController::class, 'list']);
        $r->addRoute('POST', '/admin/users', [\App\Modules\Tenants\Presentation\Controllers\AdminUserController::class, 'create']);
        $r->addRoute('GET', '/admin/users/{id:\w+}', [\App\Modules\Tenants\Presentation\Controllers\AdminUserController::class, 'get']);
        $r->addRoute('PATCH', '/admin/users/{id:\w+}', [\App\Modules\Tenants\Presentation\Controllers\AdminUserController::class, 'update']);
        $r->addRoute('DELETE', '/admin/users/{id:\w+}', [\App\Modules\Tenants\Presentation\Controllers\AdminUserController::class, 'deactivate']);
        $r->addRoute('DELETE', '/admin/users/{userId:\w+}/sessions', [\App\Modules\Tenants\Presentation\Controllers\AuthController::class, 'revoke']);
        $r->addRoute('GET', '/admin/tenants', [\App\Modules\Tenants\Presentation\Controllers\AdminTenantController::class, 'list']);
        $r->addRoute('POST', '/admin/tenants', [\App\Modules\Tenants\Presentation\Controllers\AdminTenantController::class, 'create']);
        $r->addRoute('GET', '/admin/tenants/{id:\w+}', [\App\Modules\Tenants\Presentation\Controllers\AdminTenantController::class, 'get']);
        $r->addRoute('PATCH', '/admin/tenants/{id:\w+}', [\App\Modules\Tenants\Presentation\Controllers\AdminTenantController::class, 'update']);
        $r->addRoute('DELETE', '/admin/tenants/{id:\w+}', [\App\Modules\Tenants\Presentation\Controllers\AdminTenantController::class, 'delete']);
        $r->addRoute('GET', '/health', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode(['status' => 'ok', 'service' => 'tenants-auth']));
            return $response->withHeader('Content-Type', 'application/json');});}}
final class MiddlewareChain implements RequestHandlerInterface
{
    private array $middlewares;
    private RequestHandlerInterface $finalHandler;
    private int $index = 0;
    public function __construct(array $middlewares, RequestHandlerInterface $finalHandler)
    {
        $this->middlewares = $middlewares;
        $this->finalHandler = $finalHandler;}
    public function handle(Request $request): Response
    {
        if ($this->index >= count($this->middlewares)) {
            return $this->finalHandler->handle($request);}
        $middleware = $this->middlewares[$this->index++];
        return $middleware($request, $this);
    }
}
