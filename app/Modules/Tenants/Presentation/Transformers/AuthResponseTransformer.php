<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Transformers;
use App\Modules\Tenants\Application\Results\AuthResult;
use App\Modules\Tenants\Application\Results\RefreshResult;
use App\Modules\Tenants\Application\Results\RegisterUserResult;
use App\Modules\Tenants\Application\Results\RevokeResult;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
/**
 * Respuestas JSON del micro-HIS. Los tokens van en la raíz del cuerpo, como en /api/v1/auth de Laravel,
 * y los errores siempre llevan su código HTTP real (nunca 200 con un código en el cuerpo).
 */
final class AuthResponseTransformer
{
    private ResponseFactoryInterface $responseFactory;
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;}
    public function json(array $payload, int $status = 200, array $headers = []): Response
    {
        $response = $this->responseFactory->createResponse($status)->withHeader('Content-Type', 'application/json');
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);}
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response;}
    public function error(string $message, int $status, array $extra = []): Response
    {
        return $this->json(['message' => $message] + $extra, $status);}
    public function transformRegister(RegisterUserResult $result): Response
    {
        return $this->json([
            'user_id' => $result->userId()->value(),
            'email' => $result->email()->value(),
            'tenant_id' => $result->tenantId()->value(),
            // Se entrega una sola vez para enrolar la app autenticadora.
            'totp_secret' => $result->totpSecret(),], 201);}
    public function transformAuth(AuthResult $result): Response
    {
        if ($result->isMfaRequired()) {
            return $this->json([
                'mfa_required' => true,
                'challenge_id' => $result->challengeId(),
                'method' => $result->mfaMethod(),
                'message' => 'Autenticación de doble factor requerida',], 202);}
        return $this->json([
            'access_token' => $result->accessToken()->token(),
            'token_type' => 'Bearer',
            'expires_in' => $result->expiresIn(),
            'refresh_token' => $result->refreshToken(),
            'session_id' => $result->sessionId()->value(),
            'user' => $result->user()->toArray(),]);}
    public function transformRefresh(RefreshResult $result): Response
    {
        return $this->json([
            'access_token' => $result->accessToken()->token(),
            'token_type' => 'Bearer',
            'expires_in' => $result->expiresIn(),
            'refresh_token' => $result->refreshToken(),]);}
    public function transformRevoke(RevokeResult $result): Response
    {
        return $this->json([
            'revoked_sessions' => $result->revokedSessions(),
            'message' => $result->revokedSessions() === 1 ? 'Sesión cerrada correctamente' : "{$result->revokedSessions()} sesiones revocadas",]);}}
