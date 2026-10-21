<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RefreshCommand;
use App\Modules\Tenants\Application\Results\RefreshResult;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use DomainException;
final class RefreshTokenUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TenantRepositoryInterface $tenantRepository,
        private SessionRepositoryInterface $sessionRepository,
        private RefreshTokenRepositoryInterface $refreshRepository,
        private AuditLogRepositoryInterface $auditLog,
        private RefreshTokenRotator $rotator,
        private TokenFactory $tokenFactory
    ) {}
    public function execute(RefreshCommand $command): RefreshResult
    {
        $result = $this->rotator->rotate($command->refreshToken());
        if ($result->isReuseDetected()) {
            $this->revokeEverything($result->previous(), $command->ip());
            throw new DomainException('Refresh token inválido o revocado', 401);}
        if (!$result->isSuccess()) {
            throw new DomainException($result->error() ?? 'Refresh token inválido o revocado', 401);}
        $previous = $result->previous();
        $session = $this->sessionRepository->findById($previous->sessionId());
        $user = $this->userRepository->findByIdAndTenant($previous->userId(), $previous->tenantId());
        $tenant = $this->tenantRepository->findById($previous->tenantId());
        if ($session === null || $session->isRevoked() || $user === null || $tenant === null) {
            $this->refreshRepository->revokeBySession($previous->sessionId());
            throw new DomainException('Sesión revocada', 401);}
        if (!$user->isActive() || !$tenant->isActive()) {
            throw new DomainException('Cuenta u hospital inactivo', 403);}
        $session->updateActivity();
        $this->sessionRepository->save($session);
        $accessToken = $this->tokenFactory->createAccessToken($user, $tenant, $session->id(), $previous->device());
        $this->auditLog->log(new DomainEvent(DomainEvent::TOKEN_REFRESHED, [
            'tenant_id' => $tenant->id()->value(),
            'user_id' => $user->id()->value(),
            'session_id' => $session->id()->value(),
            'new_jti' => $accessToken->jti(),
            'device_id' => $previous->device()->deviceId(),]));
        return new RefreshResult($accessToken, $result->next()->token(), $this->tokenFactory->accessTokenTtl());}
    /** Un refresh ya rotado volvió a presentarse: se revocan todas las sesiones del usuario en ese hospital. */
    private function revokeEverything(RefreshToken $reused, string $ip): void
    {
        foreach ($this->sessionRepository->findByUser($reused->tenantId(), $reused->userId()) as $session) {
            $session->revoke();
            $this->sessionRepository->save($session);}
        $this->refreshRepository->revokeAllByUser($reused->tenantId(), $reused->userId());
        $this->auditLog->log(new DomainEvent(DomainEvent::REFRESH_REUSE_DETECTED, [
            'tenant_id' => $reused->tenantId()->value(),
            'user_id' => $reused->userId()->value(),
            'session_id' => $reused->sessionId()->value(),
            'device_id' => $reused->device()->deviceId(),
            'ip' => $ip,]));}}
