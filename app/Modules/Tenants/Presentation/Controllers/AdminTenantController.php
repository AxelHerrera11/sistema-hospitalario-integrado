<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Controllers;
use App\Modules\Tenants\Application\UseCases\RegisterTenantUseCase;
use App\Modules\Tenants\Application\Commands\RegisterTenantCommand;
use App\Modules\Tenants\Application\Results\RegisterTenantResult;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
final class AdminTenantController
{
    private RegisterTenantUseCase $registerUseCase;
    public function __construct(RegisterTenantUseCase $registerUseCase)
    {$this->registerUseCase = $registerUseCase;}
    public function list(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Endpoint no implementado aún']));
        return $response->withHeader('Content-Type', 'application/json');}
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $command = new RegisterTenantCommand(
            $data['name'] ?? '',
            $data['user_quota'] ?? 100,
            $data['policies'] ?? []);
        $result = $this->registerUseCase->execute($command);
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => [
                'tenant_id' => $result->tenantId()->value(),
                'name' => $result->name(),
                'message' => 'Tenant creado correctamente'
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);}
    public function get(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('id');
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => 'No implementado', 'code' => 501]
        ]));
        return $response->withHeader('Content-Type', 'application/json');}
    public function update(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => 'No implementado', 'code' => 501]
        ]));
        return $response->withHeader('Content-Type', 'application/json');}
    public function delete(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => 'No implementado', 'code' => 501]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
