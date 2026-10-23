<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Controllers;
use App\Modules\Tenants\Application\Commands\AuthenticateCommand;
use App\Modules\Tenants\Application\Commands\MfaVerificationCommand;
use App\Modules\Tenants\Application\Commands\RefreshCommand;
use App\Modules\Tenants\Application\Commands\RevokeCommand;
use App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase;
use App\Modules\Tenants\Application\UseCases\RefreshTokenUseCase;
use App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Presentation\Requests\LoginRequest;
use App\Modules\Tenants\Presentation\Requests\MfaVerificationRequest;
use App\Modules\Tenants\Presentation\Requests\RefreshRequest;
use App\Modules\Tenants\Presentation\Requests\RevokeSessionRequest;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
final class AuthController
{
    public function __construct(
        private AuthenticateUserUseCase $authenticateUseCase,
        private RefreshTokenUseCase $refreshUseCase,
        private RevokeSessionUseCase $revokeUseCase,
        private AuthResponseTransformer $transformer
    ) {}
    public function login(Request $request): Response
    {
        $data = LoginRequest::fromRequest($request);
        $result = $this->authenticateUseCase->execute(new AuthenticateCommand(
            $data->email(),
            $data->password(),
            $data->tenantId(),
            $data->device(),
            $data->ip()));
        return $this->transformer->transformAuth($result);}
    public function verifyMfa(Request $request): Response
    {
        $data = MfaVerificationRequest::fromRequest($request);
        $result = $this->authenticateUseCase->verifyMfa(new MfaVerificationCommand($data->challengeId(), $data->code()));
        return $this->transformer->transformAuth($result);}
    public function refresh(Request $request): Response
    {
        $data = RefreshRequest::fromRequest($request);
        return $this->transformer->transformRefresh($this->refreshUseCase->execute(new RefreshCommand($data->refreshToken(), $data->ip())));}
    public function logout(Request $request): Response
    {
        $data = RevokeSessionRequest::fromRequest($request);
        $result = $this->revokeUseCase->execute(new RevokeCommand($data->tenantId(), $data->requesterId(), $data->sessionId(), null, $data->reason()));
        return $this->transformer->transformRevoke($result);}
    public function me(Request $request): Response
    {
        /** @var JwtClaims $auth */
        $auth = $request->getAttribute('auth');
        return $this->transformer->json([
            'user_id' => $auth->subject(),
            'tenant_id' => $auth->tenantId(),
            'roles' => $auth->roles(),
            'session_id' => $auth->sessionId(),]);}}
