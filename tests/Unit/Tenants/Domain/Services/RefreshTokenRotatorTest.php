<?php
declare(strict_types=1);
namespace Tests\Unit\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\RotationResult;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;
final class RefreshTokenRotatorTest extends TestCase
{
    private RefreshTokenRotator $rotator;
    private RefreshTokenRepositoryInterface $repository;
    private TokenFactory $tokenFactory;
    private User $user;
    private Tenant $tenant;
    private DeviceFingerprint $device;
    private SessionId $sessionId;
    protected function setUp(): void
    {
        $this->repository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->tokenFactory = $this->createMock(TokenFactory::class);
        $this->rotator = new RefreshTokenRotator(
            $this->repository,
            new \App\Modules\Tenants\Domain\Services\Argon2idHasher(),
            $this->tokenFactory,
            604800
        );
        $this->user = User::create(
            new \App\Modules\Tenants\Domain\ValueObjects\Email('test@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash('$argon2id$v=19$m=65536,t=4,p=3$test'),
            'Dr. Test',
            [Role::MEDICO],
            new TenantId('550e8400-e29b-41d4-a716-446655440000')
        );
        $this->tenant = Tenant::create(
            new TenantId('550e8400-e29b-41d4-a716-446655440000'),
            'Hospital Test'
        );
        $this->device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $this->sessionId = SessionId::generate();
    }
    public function testGenerateAndStore(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($token) {
                $this->assertInstanceOf(RefreshToken::class, $token);
                $this->assertNotEmpty($token->token());
                $this->assertNotEmpty($token->hash());
            });
        $result = $this->rotator->generateAndStore(
            $this->user,
            $this->tenant,
            $this->device,
            $this->sessionId,
            'access-jti-123'
        );
        $this->assertNotEmpty($result->token());
        $this->assertGreaterThan(time(), $result->expiresAt());
    }

    public function testRotateSuccess(): void
    {
        $oldToken = 'old-refresh-token';
        $oldHash = (new \App\Modules\Tenants\Domain\Services\Argon2idHasher())->hash($oldToken);
        $storedToken = RefreshToken::reconstitute(
            $oldToken,
            $oldHash,
            $this->user->id(),
            $this->tenant->id(),
            $this->device,
            'old-access-jti',
            new \DateTimeImmutable('-1 hour'),
            new \DateTimeImmutable('+6 days'),
            false
        );
        $this->repository->expects($this->once())
            ->method('findByUserIdAndDevice')
            ->willReturn($storedToken);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with($storedToken);
        $newAccessToken = new \App\Modules\Tenants\Domain\Services\AccessToken(
            'new.access.token',
            new \App\Modules\Tenants\Domain\ValueObjects\JwtClaims(
                $this->user->id()->value(),
                $this->user->roleValues(),
                $this->tenant->id()->value(),
                $this->sessionId->value(),
                'new-access-jti',
                $this->device->deviceId(),
                time(),
                time() + 900,
                time()
            )
        );
        $this->tokenFactory->expects($this->once())
            ->method('createAccessToken')
            ->willReturn($newAccessToken);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($token) {
                $this->assertInstanceOf(RefreshToken::class, $token);
                $this->assertNotEmpty($token->token());
            });
        $result = $this->rotator->rotate($oldToken, $this->user, $this->tenant, $this->device, $this->sessionId);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('new.access.token', $result->accessToken());
        $this->assertNotEmpty($result->refreshToken());
    }
    public function testRotateReuseDetected(): void
    {
        $oldToken = 'old-refresh-token';
        $oldHash = (new \App\Modules\Tenants\Domain\Services\Argon2idHasher())->hash($oldToken);
        $storedToken = RefreshToken::reconstitute(
            $oldToken,
            $oldHash,
            $this->user->id(),
            $this->tenant->id(),
            $this->device,
            'old-access-jti',
            new \DateTimeImmutable('-1 hour'),
            new \DateTimeImmutable('+6 days'),
            false
        );
        $this->repository->expects($this->once())
            ->method('findByUserIdAndDevice')
            ->willReturn($storedToken);
        $result = $this->rotator->rotate('different-token', $this->user, $this->tenant, $this->device, $this->sessionId);
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isReuseDetected());
        $this->assertEquals('Token compromise detected', $result->error());
    }
    public function testRotateTokenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findByUserIdAndDevice')
            ->willReturn(null);
        $result = $this->rotator->rotate('any-token', $this->user, $this->tenant, $this->device, $this->sessionId);
        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isReuseDetected());
        $this->assertEquals('Refresh token revocado o expirado', $result->error());
    }
}
