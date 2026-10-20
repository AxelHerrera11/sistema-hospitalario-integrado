<?php
declare(strict_types=1);
namespace Tests\Feature\Tenants;
use App\Modules\Tenants\Application\Commands\RevokeCommand;
use App\Modules\Tenants\Application\Results\RevokeResult;
use App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase;
use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\BlocklistService;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
use PHPUnit\Framework\TestCase;
final class RevokeSessionFeatureTest extends TestCase
{
    private RevokeSessionUseCase $useCase;
    private UserRepositoryInterface $userRepo;
    private SessionRepositoryInterface $sessionRepo;
    private RefreshTokenRepositoryInterface $refreshRepo;
    private BlocklistService $blocklist;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->sessionRepo = $this->createMock(SessionRepositoryInterface::class);
        $this->refreshRepo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->blocklist = $this->createMock(BlocklistService::class);
        $this->auditLog = $this->createMock(AuditLogRepositoryInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisher::class);
        $this->useCase = new RevokeSessionUseCase(
            $this->userRepo,
            $this->sessionRepo,
            $this->refreshRepo,
            $this->blocklist,
            $this->auditLog,
            $this->eventPublisher
        );
    }
    public function testRevokeOwnSession(): void
    {
        $sessionId = SessionId::generate();
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440000');
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $user = User::reconstitute(
            $userId,
            new \App\Modules\Tenants\Domain\ValueObjects\Email('test@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash('$argon2id$test'),
            'Test User',
            [Role::MEDICO],
            $tenantId,
            User::STATUS_ACTIVE,
            null,
            false,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            new \DateTimeImmutable(),
            []
        );
        $session = Session::reconstitute(
            $sessionId,
            $userId,
            $tenantId,
            DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0'),
            new \DateTimeImmutable('-1 hour'),
            new \DateTimeImmutable(),
            false
        );
        $this->sessionRepo->method('findById')->willReturn($session);
        $this->userRepo->method('findById')->willReturn($user);
        $this->blocklist->expects($this->once())->method('add');
        $this->refreshRepo->expects($this->once())->method('deleteByUserIdAndDevice');
        $this->sessionRepo->expects($this->once())->method('delete');
        $this->auditLog->expects($this->once())->method('log');
        $this->eventPublisher->expects($this->once())->method('publish');
        $command = new RevokeCommand($sessionId, null, 'logout', null);
        $result = $this->useCase->execute($command);
        $this->assertInstanceOf(RevokeResult::class, $result);
        $this->assertEquals(1, $result->revokedSessions());
    }
    public function testForceRevokeAllSessions(): void
    {
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440000');
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $requesterId = new UserId('550e8400-e29b-41d4-a716-446655440001');
        $user = User::reconstitute(
            $userId,
            new \App\Modules\Tenants\Domain\ValueObjects\Email('test@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash('$argon2id$test'),
            'Test User',
            [Role::MEDICO],
            $tenantId,
            User::STATUS_ACTIVE,
            null,
            false,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            new \DateTimeImmutable(),
            []
        );
        $sessions = [
            Session::reconstitute(
                SessionId::generate(),
                $userId,
                $tenantId,
                DeviceFingerprint::createNew('device-1', '192.168.1.1', 'Mozilla/5.0'),
                new \DateTimeImmutable('-2 hours'),
                new \DateTimeImmutable('-1 hour'),
                false
            ),
            Session::reconstitute(
                SessionId::generate(),
                $userId,
                $tenantId,
                DeviceFingerprint::createNew('device-2', '192.168.1.2', 'Mozilla/5.0'),
                new \DateTimeImmutable('-1 hour'),
                new \DateTimeImmutable(),
                false
            ),
        ];
        $this->userRepo->method('findById')->willReturn($user);
        $this->sessionRepo->method('findByUserId')->willReturn($sessions);
        $this->blocklist->expects($this->exactly(2))->method('add');
        $this->refreshRepo->expects($this->once())->method('deleteAllByUserId');
        $this->sessionRepo->expects($this->exactly(2))->method('delete');
        $this->auditLog->expects($this->once())->method('log');
        $this->eventPublisher->expects($this->once())->method('publish');
        $command = new RevokeCommand(null, $userId, 'force_revoke', $requesterId);
        $result = $this->useCase->execute($command);
        $this->assertInstanceOf(RevokeResult::class, $result);
        $this->assertEquals(2, $result->revokedSessions());
    }
    public function testRevokeNonExistentSession(): void
    {
        $sessionId = SessionId::generate();
        $this->sessionRepo->method('findById')->willReturn(null);
        $command = new RevokeCommand($sessionId, null, 'logout', null);
        $result = $this->useCase->execute($command);
        $this->assertInstanceOf(RevokeResult::class, $result);
        $this->assertEquals(0, $result->revokedSessions());
    }
}
