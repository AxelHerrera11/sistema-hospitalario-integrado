<?php
declare(strict_types=1);
namespace Tests\Feature\Tenants;
use App\Modules\Tenants\Application\Commands\AuthenticateCommand;
use App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\BlocklistService;
use App\Modules\Tenants\Domain\Services\DeviceTrustService;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\Services\TotpVerifier;
use App\Modules\Tenants\Domain\Services\WebAuthnVerifier;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
use PHPUnit\Framework\TestCase;
final class MultiTenantIsolationFeatureTest extends TestCase
{
    private AuthenticateUserUseCase $useCase;
    private UserRepositoryInterface $userRepo;
    private TenantRepositoryInterface $tenantRepo;
    private RefreshTokenRepositoryInterface $refreshRepo;
    private SessionRepositoryInterface $sessionRepo;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    private Argon2idHasher $hasher;
    private MfaPolicy $mfaPolicy;
    private TokenFactory $tokenFactory;
    private RefreshTokenRotator $refreshRotator;
    private DeviceTrustService $deviceTrust;
    private TotpVerifier $totpVerifier;
    private WebAuthnVerifier $webAuthnVerifier;
    private BlocklistService $blocklist;
    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->tenantRepo = $this->createMock(TenantRepositoryInterface::class);
        $this->refreshRepo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->sessionRepo = $this->createMock(SessionRepositoryInterface::class);
        $this->auditLog = $this->createMock(AuditLogRepositoryInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisher::class);
        $this->hasher = new Argon2idHasher();
        $this->mfaPolicy = $this->createMock(MfaPolicy::class);
        $this->tokenFactory = $this->createMock(TokenFactory::class);
        $this->refreshRotator = $this->createMock(RefreshTokenRotator::class);
        $this->deviceTrust = $this->createMock(DeviceTrustService::class);
        $this->totpVerifier = $this->createMock(TotpVerifier::class);
        $this->webAuthnVerifier = $this->createMock(WebAuthnVerifier::class);
        $this->blocklist = $this->createMock(BlocklistService::class);
        $this->useCase = new AuthenticateUserUseCase(
            $this->userRepo,
            $this->tenantRepo,
            $this->refreshRepo,
            $this->sessionRepo,
            $this->auditLog,
            $this->eventPublisher,
            $this->hasher,
            $this->mfaPolicy,
            $this->tokenFactory,
            $this->refreshRotator,
            $this->deviceTrust,
            $this->totpVerifier,
            $this->webAuthnVerifier,
            $this->blocklist
        );
    }
    public function testUserFromTenantACannotAccessTenantB(): void
    {
        $tenantA = new TenantId('550e8400-e29b-41d4-a716-446655440001');
        $tenantB = new TenantId('550e8400-e29b-41d4-a716-446655440002');
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440000');
        $passwordHash = $this->hasher->hash('CorrectPass123!');
        $user = User::reconstitute(
            $userId,
            new Email('medico@hospital-a.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash($passwordHash),
            'Dr. Medico',
            [Role::MEDICO],
            $tenantA,
            User::STATUS_ACTIVE,
            null,
            false,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            new \DateTimeImmutable(),
            [$passwordHash]
        );
        $this->userRepo->method('findByEmailAndTenant')
            ->with(new \App\Modules\Tenants\Domain\ValueObjects\Email('medico@hospital-a.com'), $tenantB)
            ->willReturn(null);
        $this->auditLog->expects($this->once())->method('log');
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('medico@hospital-a.com'),
            'CorrectPass123!',
            $tenantB,
            $device,
            '192.168.1.1'
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Credenciales inválidas');
        $this->useCase->execute($command);
    }
    public function testInactiveTenantBlocksAccess(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440000');
        $passwordHash = $this->hasher->hash('CorrectPass123!');
        $user = User::reconstitute(
            $userId,
            new Email('medico@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash($passwordHash),
            'Dr. Medico',
            [Role::MEDICO],
            $tenantId,
            User::STATUS_ACTIVE,
            null,
            false,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            new \DateTimeImmutable(),
            [$passwordHash]
        );
        $tenant = \App\Modules\Tenants\Domain\Entities\Tenant::create($tenantId, 'Hospital Suspendido');
        $reflection = new \ReflectionClass($tenant);
        $reflection->getProperty('status')->setValue($tenant, \App\Modules\Tenants\Domain\Entities\Tenant::STATUS_SUSPENDED);
        $this->userRepo->method('findByEmailAndTenant')->willReturn($user);
        $this->tenantRepo->method('findById')->willReturn($tenant);
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('medico@hospital.com'),
            'CorrectPass123!',
            $tenantId,
            $device,
            '192.168.1.1'
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tenant inactivo');
        $this->useCase->execute($command);
    }
    public function testUserRolesAreScopedToTenant(): void
    {
        $tenantA = new TenantId('550e8400-e29b-41d4-a716-446655440001');
        $tenantB = new TenantId('550e8400-e29b-41d4-a716-446655440002');
        $this->userRepo->method('findByEmailAndTenant')
            ->willReturnCallback(function ($email, $tenantId) {
                if ($tenantId->value() === $tenantA->value()) {
                    return User::reconstitute(
                        new UserId('550e8400-e29b-41d4-a716-446655440000'),
                        $email,
                        \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash('$argon2id$test'),
                        'Dr. Cross',
                        [Role::MEDICO],
                        $tenantA,
                        User::STATUS_ACTIVE,
                        null,
                        false,
                        new \DateTimeImmutable(),
                        new \DateTimeImmutable(),
                        null,
                        new \DateTimeImmutable(),
                        []
                    );
                }
                return null;
            });
        $tenantATenant = \App\Modules\Tenants\Domain\Entities\Tenant::create($tenantA, 'Hospital A');
        $this->tenantRepo->method('findById')->willReturn($tenantATenant);
        $this->mfaPolicy->method('evaluate')->willReturn(
            new \App\Modules\Tenants\Domain\Services\MfaRequirement(false)
        );
        $accessToken = new \App\Modules\Tenants\Domain\Services\AccessToken(
            'access.token',
            new \App\Modules\Tenants\Domain\ValueObjects\JwtClaims(
                '550e8400-e29b-41d4-a716-446655440000',
                ['MEDICO'],
                $tenantA->value(),
                'session-123',
                'jti-123',
                'device-123',
                time(),
                time() + 900,
                time()
            )
        );
        $this->tokenFactory->method('createAccessToken')->willReturn($accessToken);
        $this->refreshRotator->method('generateAndStore')->willReturn(
            new \App\Modules\Tenants\Domain\Services\RefreshTokenResult('refresh', time() + 604800)
        );
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('cross@hospital.com'),
            'CorrectPass123!',
            $tenantA,
            $device,
            '192.168.1.1'
        );
        $result = $this->useCase->execute($command);
        $this->assertFalse($result->isMfaRequired());
        $this->assertEquals(['MEDICO'], $result->accessToken()->claims()->roles());
    }
}
