<?php
declare(strict_types=1);
namespace Tests\Feature\Tenants;
use App\Modules\Tenants\Application\Commands\RefreshCommand;
use App\Modules\Tenants\Application\Results\RefreshResult;
use App\Modules\Tenants\Application\UseCases\RefreshTokenUseCase;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\RotationResult;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
use PHPUnit\Framework\TestCase;
final class RefreshTokenFeatureTest extends TestCase
{
    private RefreshTokenUseCase $useCase;
    private UserRepositoryInterface $userRepo;
    private RefreshTokenRepositoryInterface $refreshRepo;
    private SessionRepositoryInterface $sessionRepo;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    private RefreshTokenRotator $rotator;
    private TokenFactory $tokenFactory;
    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->refreshRepo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->sessionRepo = $this->createMock(SessionRepositoryInterface::class);
        $this->auditLog = $this->createMock(AuditLogRepositoryInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisher::class);
        $this->rotator = $this->createMock(RefreshTokenRotator::class);
        $this->tokenFactory = $this->createMock(TokenFactory::class);
        $this->useCase = new RefreshTokenUseCase(
            $this->userRepo,
            $this->refreshRepo,
            $this->sessionRepo,
            $this->auditLog,
            $this->eventPublisher,
            $this->rotator,
            $this->tokenFactory
        );
    }
    public function testSuccessfulTokenRefresh(): void
    {
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440000');
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $user = User::reconstitute(
            $userId,
            new \App\Modules\Tenants\Domain\ValueObjects\Email('test@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash('$argon2id$test'),
            'Test User',
            [\App\Modules\Tenants\Domain\ValueObjects\Role::MEDICO],
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
        $storedToken = RefreshToken::reconstitute(
            'valid-refresh-token',
            '$argon2id$hashed',
            $userId,
            $tenantId,
            \App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0'),
            'old-access-jti',
            new \DateTimeImmutable('-1 hour'),
            new \DateTimeImmutable('+6 days'),
            false
        );
        $this->refreshRepo->method('findByTokenPrefix')->willReturn($storedToken);
        $this->userRepo->method('findById')->willReturn($user);
        $newAccessToken = new \App\Modules\Tenants\Domain\Services\AccessToken(
            'new.access.token',
            new \App\Modules\Tenants\Domain\ValueObjects\JwtClaims(
                $userId->value(),
                ['MEDICO'],
                $tenantId->value(),
                'new-session',
                'new-jti',
                'device-123',
                time(),
                time() + 900,
                time()
            )
        );
        $this->rotator->method('rotate')->willReturn(
            RotationResult::success(
                'new.access.token',
                'new-refresh-token',
                time() + 900,
                time() + 604800
            )
        );
        $this->sessionRepo->expects($this->once())->method('findById');
        $this->auditLog->expects($this->once())->method('log');
        $this->eventPublisher->expects($this->never())->method('publish'); // No reuse detected
        $command = new RefreshCommand('valid-refresh-token');
        $result = $this->useCase->execute($command);
        $this->assertInstanceOf(RefreshResult::class, $result);
        $this->assertEquals('new.access.token', $result->accessToken()->token());
        $this->assertEquals('new-refresh-token', $result->refreshToken());
    }
    public function testFailsWithInvalidRefreshToken(): void
    {
        $this->refreshRepo->method('findByTokenPrefix')->willReturn(null);
        $command = new RefreshCommand('invalid-token');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Refresh token inválido o revocado');
        $this->useCase->execute($command);
    }
    public function testFailsWithReuseDetected(): void
    {
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440000');
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $user = User::reconstitute(
            $userId,
            new \App\Modules\Tenants\Domain\ValueObjects\Email('test@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash('$argon2id$test'),
            'Test User',
            [\App\Modules\Tenants\Domain\ValueObjects\Role::MEDICO],
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
        $storedToken = RefreshToken::reconstitute(
            'valid-refresh-token',
            '$argon2id$hashed',
            $userId,
            $tenantId,
            \App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0'),
            'old-access-jti',
            new \DateTimeImmutable('-1 hour'),
            new \DateTimeImmutable('+6 days'),
            false
        );
        $this->refreshRepo->method('findByTokenPrefix')->willReturn($storedToken);
        $this->userRepo->method('findById')->willReturn($user);
        $this->rotator->method('rotate')->willReturn(
            RotationResult::reuseDetected($userId->value())
        );
        $this->auditLog->expects($this->once())->method('log');
        $this->eventPublisher->expects($this->once())->method('publish');
        $command = new RefreshCommand('reused-token');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token compromise detected');
        $this->useCase->execute($command);
    }
}
