<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Transformers;
use App\Modules\Tenants\Application\Results\AuthResult;
use App\Modules\Tenants\Application\Results\RefreshResult;
use App\Modules\Tenants\Application\Results\RegisterUserResult;
use App\Modules\Tenants\Application\Results\RevokeResult;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseFactoryInterface;
final class AuthResponseTransformer
{
    private ResponseFactoryInterface $responseFactory;
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }
    public function transformRegister(Response $response, RegisterUserResult $result, int $status = 201): Response
    {
        $payload = [
            'success' => true,
            'data' => [
                'user_id' => $result->userId()->value(),
                'email' => $result->email()->value(),
                'tenant_id' => $result->tenantId()->value(),
                'message' => 'Usuario creado. Credenciales temporales enviadas por email.',
            ],
        ];
        $newResponse = $this->responseFactory->createResponse($status);
        $newResponse->getBody()->write(json_encode($payload));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }
    public function transformAuth(Response $response, AuthResult $result): Response
    {
        $payload = [
            'success' => true,
            'data' => [
                'access_token' => $result->accessToken()->token(),
                'token_type' => 'Bearer',
                'expires_in' => $result->expiresIn(),
                'refresh_token' => $result->refreshToken(),
                'user' => $result->user()->toArray(),
                'session_id' => $result->sessionId()->value(),
            ],
        ];
        $newResponse = $this->responseFactory->createResponse(200);
        $newResponse->getBody()->write(json_encode($payload));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }
    public function transformMfaChallenge(Response $response, AuthResult $result): Response
    {
        $payload = [
            'success' => true,
            'mfa_required' => true,
            'challenge_id' => $result->challengeId(),
            'method' => $result->mfaMethod(),
            'message' => 'Autenticación de doble factor requerida',
        ];
        $newResponse = $this->responseFactory->createResponse(200);
        $newResponse->getBody()->write(json_encode($payload));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }
    public function transformRefresh(Response $response, RefreshResult $result): Response
    {
        $payload = [
            'success' => true,
            'data' => [
                'access_token' => $result->accessToken()->token(),
                'token_type' => 'Bearer',
                'expires_in' => $result->expiresIn(),
                'refresh_token' => $result->refreshToken(),
            ],
        ];
        $newResponse = $this->responseFactory->createResponse(200);
        $newResponse->getBody()->write(json_encode($payload));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }
    public function transformRevoke(Response $response, RevokeResult $result): Response
    {
        $payload = [
            'success' => true,
            'data' => [
                'revoked_sessions' => $result->revokedSessions(),
                'message' => $result->revokedSessions() === 1
                    ? 'Sesión cerrada correctamente'
                    : "{$result->revokedSessions()} sesiones revocadas",
            ],
        ];
        $newResponse = $this->responseFactory->createResponse(200);
        $newResponse->getBody()->write(json_encode($payload));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }
    public function transformError(Response $response, string $message, int $status = 400): Response
    {
        $payload = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status,
            ],
        ];
        $newResponse = $this->responseFactory->createResponse($status);
        $newResponse->getBody()->write(json_encode($payload));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }
}
