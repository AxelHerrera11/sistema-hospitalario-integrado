<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RevokeCommand;
use App\Modules\Tenants\Application\Results\RevokeResult;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use DomainException;
/**
 * Revoca sesiones dentro de un hospital. JwtAuthMiddleware rechaza cualquier access token
 * cuya sesión (claim session_id) esté revocada, así que el efecto es inmediato.
 */
final class RevokeSessionUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SessionRepositoryInterface $sessionRepository,
        private RefreshTokenRepositoryInterface $refreshRepository,
        private AuditLogRepositoryInterface $auditLog
    ) {}
    public function execute(RevokeCommand $command): RevokeResult
    {
        $tenantId = $command->tenantId();
        $targetId = $command->isForceRevoke() ? $command->targetUserId() : $command->requesterId();
        if ($command->isForceRevoke()) {
            if ($this->userRepository->findByIdAndTenant($targetId, $tenantId) === null) {
                throw new DomainException('Usuario no encontrado en este hospital', 404);}
            $sessions = $this->sessionRepository->findByUser($tenantId, $targetId);
            $this->refreshRepository->revokeAllByUser($tenantId, $targetId);
        } else {
            $session = $this->sessionRepository->findById($command->sessionId());
            if ($session === null || !$session->belongsTo($tenantId, $targetId)) {
                throw new DomainException('Sesión no encontrada', 404);}
            $sessions = [$session];
            $this->refreshRepository->revokeBySession($session->id());}
        $count = 0;
        foreach ($sessions as $session) {
            if (!$session->isRevoked()) {
                $session->revoke();
                $this->sessionRepository->save($session);
                $count++;}}
        $this->auditLog->log(new DomainEvent(DomainEvent::SESSION_REVOKED, [
            'tenant_id' => $tenantId->value(),
            'user_id' => $targetId->value(),
            'reason' => $command->reason(),
            'revoked_count' => $count,
            'requester_id' => $command->requesterId()->value(),]));
        return new RevokeResult($count);}}
