<?php
declare(strict_types=1);
namespace Tests\Feature\Tenants;
use App\Modules\Tenants\Application\Commands\AuthenticateCommand;
use App\Modules\Tenants\Application\Commands\MfaVerificationCommand;
use App\Modules\Tenants\Application\Results\AuthResult;
use App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase;
use App\Modules\Tenants\Domain\Entities\Tenant;
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
use App\Modules\Tenants\Domain\Services\MfaRequirement;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\Services\TotpVerifier;
use App\Modules\Tenants\Domain\Services\WebAuthnVerifier;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
use PHPUnit\Framework\TestCase;
final class LoginMfaFeatureTest extends TestCase
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
    public function testSuccessfulLoginWithoutMfa(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440001');
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
        $tenant = Tenant::create($tenantId, 'Hospital Central');
        $this->userRepo->method('findByEmailAndTenant')->willReturn($user);
        $this->tenantRepo->method('findById')->willReturn($tenant);
        $this->mfaPolicy->method('evaluate')->willReturn(
            new MfaRequirement(false)
        );
        $accessToken = new \App\Modules\Tenants\Domain\Services\AccessToken(
            'access.token.jwt',
            new \App\Modules\Tenants\Domain\ValueObjects\JwtClaims(
                $userId->value(),
                ['MEDICO'],
                $tenantId->value(),
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
            new \App\Modules\Tenants\Domain\Services\RefreshTokenResult('refresh-token', time() + 604800)
        );
        $this->sessionRepo->expects($this->once())->method('save');
        $this->auditLog->expects($this->once())->method('log');
        $this->eventPublisher->expects($this->once())->method('publish');
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('medico@hospital.com'),
            'CorrectPass123!',
            $tenantId,
            $device,
            '192.168.1.1'
        );
        $result = $this->useCase->execute($command);
        $this->assertInstanceOf(AuthResult::class, $result);
        $this->assertFalse($result->isMfaRequired());
        $this->assertEquals('access.token.jwt', $result->accessToken()->token());
    }
    public function testSuccessfulLoginWithMfaTotp(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440001');
        $passwordHash = $this->hasher->hash('CorrectPass123!');
        $user = User::reconstitute(
            $userId,
            new Email('admin@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash($passwordHash),
            'Admin Hospital',
            [Role::TENANT_ADMIN],
            $tenantId,
            User::STATUS_ACTIVE,
            'JBSWY3DPEHPK3PXP',
            true,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            new \DateTimeImmutable(),
            [$passwordHash]
        );
        $tenant = Tenant::create($tenantId, 'Hospital Central');
        $this->userRepo->method('findByEmailAndTenant')->willReturn($user);
        $this->tenantRepo->method('findById')->willReturn($tenant);

        $this->mfaPolicy->method('evaluate')->willReturn(
            new MfaRequirement(true, 'TOTP')
        );
        $challengeResult = new AuthResult(
            null,
            '',
            0,
            $user,
            null,
            true,
            'challenge-123',
            'TOTP'
        );
        $accessToken = new \App\Modules\Tenants\Domain\Services\AccessToken(
            'access.token.jwt',
            new \App\Modules\Tenants\Domain\ValueObjects\JwtClaims(
                $userId->value(),
                ['TENANT_ADMIN'],
                $tenantId->value(),
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
            new \App\Modules\Tenants\Domain\Services\RefreshTokenResult('refresh-token', time() + 604800)
        );
        $this->totpVerifier->method('verify')->willReturn(true);
        $this->sessionRepo->expects($this->once())->method('save');
        $this->auditLog->expects($this->exactly(2))->method('log'); // MFA_CHALLENGED + MFA_SUCCEEDED + LOGIN_SUCCESS
        $this->eventPublisher->expects($this->once())->method('publish');
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('admin@hospital.com'),
            'CorrectPass123!',
            $tenantId,
            $device,
            '192.168.1.1'
        );
        $result = $this->useCase->execute($command);
        $this->assertTrue($result->isMfaRequired());
        $this->assertEquals('challenge-123', $result->challengeId());
        $this->assertEquals('TOTP', $result->mfaMethod());
    }
    public function testFailsWithInvalidCredentials(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $this->userRepo->method('findByEmailAndTenant')->willReturn(null);
        $this->auditLog->expects($this->once())->method('log');
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('inexistente@hospital.com'),
            'WrongPass123!',
            $tenantId,
            $device,
            '192.168.1.1'
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Credenciales inválidas');
        $this->useCase->execute($command);
    }
    public function testFailsWithWrongPassword(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440001');
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
        $this->userRepo->method('findByEmailAndTenant')->willReturn($user);
        $this->auditLog->expects($this->once())->method('log');
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('medico@hospital.com'),
            'WrongPass123!',
            $tenantId,
            $device,
            '192.168.1.1'
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Credenciales inválidas');
        $this->useCase->execute($command);
    }
    public function testFailsWithInactiveUser(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $userId = new UserId('550e8400-e29b-41d4-a716-446655440001');
        $passwordHash = $this->hasher->hash('CorrectPass123!');
        $user = User::reconstitute(
            $userId,
            new Email('medico@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash($passwordHash),
            'Dr. Medico',
            [Role::MEDICO],
            $tenantId,
            User::STATUS_INACTIVE,
            null,
            false,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            new \DateTimeImmutable(),
            [$passwordHash]
        );
        $this->userRepo->method('findByEmailAndTenant')->willReturn($user);
        $device = DeviceFingerprint::createNew('device-123', '192.168.1.1', 'Mozilla/5.0');
        $command = new AuthenticateCommand(
            new Email('medico@hospital.com'),
            'CorrectPass123!',
            $tenantId,
            $device,
            '192.168.1.1'
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cuenta desactivada');
        $this->useCase->execute($command);
    }
}
