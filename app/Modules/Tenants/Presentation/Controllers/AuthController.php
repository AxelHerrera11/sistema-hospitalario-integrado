<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Controllers;
use App\Modules\Tenants\Application\Commands\AuthenticateCommand;
use App\Modules\Tenants\Application\Commands\MfaVerificationCommand;
use App\Modules\Tenants\Application\Commands\RefreshCommand;
use App\Modules\Tenants\Application\Commands\RegisterUserCommand;
use App\Modules\Tenants\Application\Commands\RevokeCommand;
use App\Modules\Tenants\Application\Results\AuthResult;
use App\Modules\Tenants\Application\Results\RefreshResult;
use App\Modules\Tenants\Application\Results\RegisterUserResult;
use App\Modules\Tenants\Application\Results\RevokeResult;
use App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase;
use App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase;
use App\Modules\Tenants\Application\UseCases\RegisterUserUseCase;
use App\Modules\Tenants\Application\UseCases\RefreshTokenUseCase;
use App\Modules\Tenants\Presentation\Requests\LoginRequest;
use App\Modules\Tenants\Presentation\Requests\MfaVerificationRequest;
use App\Modules\Tenants\Presentation\Requests\RefreshRequest;
use App\Modules\Tenants\Presentation\Requests\RegisterUserRequest;
use App\Modules\Tenants\Presentation\Requests\RevokeSessionRequest;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
final class AuthController
{
    private RegisterUserUseCase $registerUseCase;
    private AuthenticateUserUseCase $authenticateUseCase;
    private RefreshTokenUseCase $refreshUseCase;
    private RevokeSessionUseCase $revokeUseCase;
    private AuthResponseTransformer $transformer;
    public function __construct(
        RegisterUserUseCase $registerUseCase,
        AuthenticateUserUseCase $authenticateUseCase,
        RefreshTokenUseCase $refreshUseCase,
        RevokeSessionUseCase $revokeUseCase,
        AuthResponseTransformer $transformer
    ) {
        $this->registerUseCase = $registerUseCase;
        $this->authenticateUseCase = $authenticateUseCase;
        $this->refreshUseCase = $refreshUseCase;
        $this->revokeUseCase = $revokeUseCase;
        $this->transformer = $transformer;}
    public function register(Request $request, Response $response): Response
    {
        $data = RegisterUserRequest::fromRequest($request);
        $command = new RegisterUserCommand(
            $data->email(),
            $data->name(),
            $data->password(),
            $data->roles(),
            $data->tenantId(),
            $data->createdBy(),
            $data->mfaRequired());
        $result = $this->registerUseCase->execute($command);
        return $this->transformer->transformRegister($response, $result, 201);
    }
    public function login(Request $request, Response $response): Response
    {
        $data = LoginRequest::fromRequest($request);
        $command = new AuthenticateCommand(
            $data->email(),
            $data->password(),
            $data->tenantId(),
            $data->device(),
            $data->ip()
        );
        $result = $this->authenticateUseCase->execute($command);
        if ($result->isMfaRequired()) {
            return $this->transformer->transformMfaChallenge($response, $result);}
        return $this->transformer->transformAuth($response, $result);}
    public function verifyMfa(Request $request, Response $response): Response
    {
        $data = MfaVerificationRequest::fromRequest($request);
        $command = new MfaVerificationCommand(
            $data->challengeId(),
            $data->code(),
            $data->method()
        );
        throw new \RuntimeException('MFA verification requires challenge context retrieval');}
    public function refresh(Request $request, Response $response): Response
    {
        $data = RefreshRequest::fromRequest($request);
        $command = new RefreshCommand($data->refreshToken());
        $result = $this->refreshUseCase->execute($command);
        return $this->transformer->transformRefresh($response, $result);}
    public function revoke(Request $request, Response $response): Response
    {
        $data = RevokeSessionRequest::fromRequest($request);
        $command = new RevokeCommand(
            $data->sessionId(),
            $data->targetUserId(),
            $data->reason(),
            $data->requesterId());
        $result = $this->revokeUseCase->execute($command);
        return $this->transformer->transformRevoke($response, $result);
    }
}
