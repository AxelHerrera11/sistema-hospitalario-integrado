<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Controllers;
use App\Modules\Tenants\Application\Commands\RegisterUserCommand;
use App\Modules\Tenants\Application\UseCases\RegisterUserUseCase;
use App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase;
use App\Modules\Tenants\Application\Results\RegisterUserResult;
use App\Modules\Tenants\Application\Results\RevokeResult;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
final class AdminUserController
{
    private RegisterUserUseCase $registerUseCase;
    private RevokeSessionUseCase $revokeUseCase;
    private AuthResponseTransformer $transformer;
    public function __construct(
        RegisterUserUseCase $registerUseCase,
        RevokeSessionUseCase $revokeUseCase,
        AuthResponseTransformer $transformer) {
        $this->registerUseCase = $registerUseCase;
        $this->revokeUseCase = $revokeUseCase;
        $this->transformer = $transformer;}
    public function list(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Endpoint no implementado aún']));
        return $response->withHeader('Content-Type', 'application/json');}
    public function create(Request $request, Response $response): Response
    {
        $data = \App\Modules\Tenants\Presentation\Requests\RegisterUserRequest::fromRequest($request);
        $command = new RegisterUserCommand(
            $data->email(),
            $data->name(),
            $data->password(),
            $data->roles(),
            $data->tenantId(),
            $data->createdBy(),
            $data->mfaRequired());
        $result = $this->registerUseCase->execute($command);
        return $this->transformer->transformRegister($response, $result, 201);}
    public function get(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('id');
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => 'No implementado', 'code' => 501]]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function update(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => 'No implementado', 'code' => 501]]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function deactivate(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => ['message' => 'No implementado', 'code' => 501]]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
