<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RefreshCommand;
use App\Modules\Tenants\Application\Results\RefreshResult;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Events\TokenRefreshed;
use App\Modules\Tenants\Domain\Events\RefreshReuseDetected;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\Services\RotationResult;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
final class RefreshTokenUseCase
{
    private UserRepositoryInterface $userRepository;
    private RefreshTokenRepositoryInterface $refreshRepository;
    private SessionRepositoryInterface $sessionRepository;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    private RefreshTokenRotator $rotator;
    private TokenFactory $tokenFactory;
    public function __construct(
        UserRepositoryInterface $userRepository,
        RefreshTokenRepositoryInterface $refreshRepository,
        SessionRepositoryInterface $sessionRepository,
        AuditLogRepositoryInterface $auditLog,
        EventPublisher $eventPublisher,
        RefreshTokenRotator $rotator,
        TokenFactory $tokenFactory
    ) {
        $this->userRepository = $userRepository;
        $this->refreshRepository = $refreshRepository;
        $this->sessionRepository = $sessionRepository;
        $this->auditLog = $auditLog;
        $this->eventPublisher = $eventPublisher;
        $this->rotator = $rotator;
        $this->tokenFactory = $tokenFactory;}
    public function execute(RefreshCommand $command): RefreshResult
    {
        $refreshToken = $command->refreshToken();
        $stored = $this->refreshRepository->findByTokenPrefix($refreshToken);
        if ($stored === null) {
            throw new \DomainException('Refresh token inválido o revocado');}
        $user = $this->userRepository->findById($stored->userId());
        if ($user === null) {
            throw new \DomainException('Usuario no encontrado');}
        $tenant = new \App\Modules\Tenants\Domain\Entities\Tenant(
            $stored->tenantId(),
            '',
            \App\Modules\Tenants\Domain\Entities\Tenant::STATUS_ACTIVE );
        $device = $stored->device();
        $sessionId = SessionId::generate();
        $result = $this->rotator->rotate($refreshToken, $user, $tenant, $device, $sessionId);
        if (!$result->isSuccess()) {
            if ($result->isReuseDetected()) {
                $this->auditLog->log(new RefreshReuseDetected(
                    $user->id(),
                    $tenant->id(),
                    $device,
                    'unknown'));
                $this->eventPublisher->publish(new RefreshReuseDetected(
                    $user->id(),
                    $tenant->id(),
                    $device,
                    'unknown'));
                $this->revokeAllUserSessions($user, $tenant->id());}
            throw new \DomainException($result->error() ?? 'Error rotando token');}
        $this->sessionRepository->findById($sessionId)?->updateActivity();
        $this->auditLog->log(new TokenRefreshed(
            $user->id(),
            $tenant->id(),
            $result->accessToken(),
            $result->accessToken(),
            $device));
        return new RefreshResult(
            $this->tokenFactory->createAccessToken($user, $tenant, $sessionId, $device),
            $result->refreshToken(),
            $result->accessExpiresAt() - time());}
    private function revokeAllUserSessions(User $user, \App\Modules\Tenants\Domain\ValueObjects\TenantId $tenantId): void
    {
        $this->refreshRepository->deleteAllByUserId($user->id());
        $this->sessionRepository->deleteAllByUserId($user->id());}}
