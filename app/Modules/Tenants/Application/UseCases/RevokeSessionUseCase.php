<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RevokeCommand;
use App\Modules\Tenants\Application\Results\RevokeResult;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Events\SessionRevoked;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\BlocklistService;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
final class RevokeSessionUseCase
{
    private UserRepositoryInterface $userRepository;
    private SessionRepositoryInterface $sessionRepository;
    private RefreshTokenRepositoryInterface $refreshRepository;
    private BlocklistService $blocklist;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    public function __construct(
        UserRepositoryInterface $userRepository,
        SessionRepositoryInterface $sessionRepository,
        RefreshTokenRepositoryInterface $refreshRepository,
        BlocklistService $blocklist,
        AuditLogRepositoryInterface $auditLog,
        EventPublisher $eventPublisher
    ) {
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
        $this->refreshRepository = $refreshRepository;
        $this->blocklist = $blocklist;
        $this->auditLog = $auditLog;
        $this->eventPublisher = $eventPublisher;}
    public function execute(RevokeCommand $command): RevokeResult
    {
        $revokedCount = 0;
        if ($command->isForceRevoke()) {
            $revokedCount = $this->revokeAllSessions($command->targetUserId(), $command->reason(), $command->requesterId());} else {
            $revokedCount = $this->revokeOwnSession($command->sessionId(), $command->reason());}
        return new RevokeResult($revokedCount);}
    private function revokeOwnSession(SessionId $sessionId, string $reason): int
    {
        $session = $this->sessionRepository->findById($sessionId);
        if ($session === null) { return 0;}
        $this->blocklist->add($session->id()->value(), $session->createdAt()->getTimestamp() + 900);
        $this->refreshRepository->deleteByUserIdAndDevice($session->userId(), $session->device()->deviceId());
        $this->sessionRepository->delete($sessionId);
        $this->auditLog->log(new SessionRevoked(
            $session->userId(),
            $session->tenantId(),
            $reason,
            1 ));
        $this->eventPublisher->publish(new SessionRevoked(
            $session->userId(),
            $session->tenantId(),
            $reason,
            1 )); return 1;}
    private function revokeAllSessions(UserId $userId, string $reason, ?UserId $requesterId): int
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return 0; }
        $sessions = $this->sessionRepository->findByUserId($userId);
        $count = 0;
        foreach ($sessions as $session) {
            if (!$session->isRevoked()) {
                $this->blocklist->add($session->id()->value(), $session->createdAt()->getTimestamp() + 900);
                $this->sessionRepository->delete($session->id());
                $count++; } }
        $this->refreshRepository->deleteAllByUserId($userId);
        $this->auditLog->log(new SessionRevoked(
            $userId,
            $user->tenantId(),
            $reason,
            $count,
            $requesterId ));
        $this->eventPublisher->publish(new SessionRevoked(
            $userId,
            $user->tenantId(),
            $reason,
            $count,
            $requesterId )); return $count;}}
