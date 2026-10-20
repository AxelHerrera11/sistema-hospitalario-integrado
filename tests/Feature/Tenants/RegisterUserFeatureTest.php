<?php
declare(strict_types=1);
namespace Tests\Feature\Tenants;
use App\Modules\Tenants\Application\Commands\RegisterUserCommand;
use App\Modules\Tenants\Application\UseCases\RegisterUserUseCase;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\PasswordPolicy;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EmailNotificationService;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
use PHPUnit\Framework\TestCase;
final class RegisterUserFeatureTest extends TestCase
{
    private RegisterUserUseCase $useCase;
    private UserRepositoryInterface $userRepo;
    private TenantRepositoryInterface $tenantRepo;
    private PasswordPolicy $passwordPolicy;
    private MfaPolicy $mfaPolicy;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    private EmailNotificationService $emailService;
    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->tenantRepo = $this->createMock(TenantRepositoryInterface::class);
        $this->passwordPolicy = $this->createMock(PasswordPolicy::class);
        $this->mfaPolicy = $this->createMock(MfaPolicy::class);
        $this->auditLog = $this->createMock(AuditLogRepositoryInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisher::class);
        $this->emailService = $this->createMock(EmailNotificationService::class);
        $this->useCase = new RegisterUserUseCase(
            $this->userRepo,
            $this->tenantRepo,
            $this->passwordPolicy,
            $this->mfaPolicy,
            $this->auditLog,
            $this->eventPublisher,
            $this->emailService
        );}
    public function testSuccessfulUserRegistration(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $createdBy = new UserId('550e8400-e29b-41d4-a716-446655440001');
        $tenant = Tenant::create($tenantId, 'Hospital Central', 100);
        $this->tenantRepo->method('findById')->willReturn($tenant);
        $this->userRepo->method('existsByEmailAndTenant')->willReturn(false);
        $this->passwordPolicy->method('validate')->willReturn(
            new \App\Modules\Tenants\Domain\Services\ValidationResult(true));
        $this->mfaPolicy->method('evaluate')->willReturn(
            new \App\Modules\Tenants\Domain\Services\MfaRequirement(false));
        $this->userRepo->expects($this->once())->method('save');
        $this->tenantRepo->expects($this->once())->method('save');
        $this->auditLog->expects($this->once())->method('log');
        $this->eventPublisher->expects($this->once())->method('publish');
        $this->emailService->expects($this->once())->method('sendActivationEmail');
        $command = new RegisterUserCommand(
            new Email('nuevo@hospital.com'),
            'Dr. Nuevo',
            'SecurePass123!',
            [Role::MEDICO],
            $tenantId,
            $createdBy,
            false);
        $result = $this->useCase->execute($command);
        $this->assertNotNull($result->userId());
        $this->assertEquals('nuevo@hospital.com', $result->email()->value());
        $this->assertNotEmpty($result->tempPassword());}
    public function testFailsWhenTenantInactive(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $tenant = Tenant::create($tenantId, 'Hospital Suspendido');
        $reflection = new \ReflectionClass($tenant);
        $reflection->getProperty('status')->setValue($tenant, Tenant::STATUS_SUSPENDED);
        $this->tenantRepo->method('findById')->willReturn($tenant);
        $this->auditLog->expects($this->once())->method('log');
        $command = new RegisterUserCommand(
            new Email('nuevo@hospital.com'),
            'Dr. Nuevo',
            'SecurePass123!',
            [Role::MEDICO],
            $tenantId,
            new UserId('550e8400-e29b-41d4-a716-446655440001'),);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tenant inactivo');
        $this->useCase->execute($command);}
    public function testFailsWhenQuotaExceeded(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $tenant = Tenant::create($tenantId, 'Hospital Lleno', 10);
        $reflection = new \ReflectionClass($tenant);
        $reflection->getProperty('usersCount')->setValue($tenant, 10);
        $this->tenantRepo->method('findById')->willReturn($tenant);
        $this->auditLog->expects($this->once())->method('log');
        $command = new RegisterUserCommand(
            new Email('nuevo@hospital.com'),
            'Dr. Nuevo',
            'SecurePass123!',
            [Role::MEDICO],
            $tenantId,
            new UserId('550e8400-e29b-41d4-a716-446655440001'),);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cupo de usuarios excedido');
        $this->useCase->execute($command);}
    public function testFailsWhenEmailAlreadyExists(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $tenant = Tenant::create($tenantId, 'Hospital Central', 100);
        $this->tenantRepo->method('findById')->willReturn($tenant);
        $this->userRepo->method('existsByEmailAndTenant')->willReturn(true);
        $command = new RegisterUserCommand(
            new Email('existente@hospital.com'),
            'Dr. Nuevo',
            'SecurePass123!',
            [Role::MEDICO],
            $tenantId,
            new UserId('550e8400-e29b-41d4-a716-446655440001'),);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email ya registrado en este tenant');
        $this->useCase->execute($command);}
    public function testFailsWhenPasswordPolicyViolated(): void
    {
        $tenantId = new TenantId('550e8400-e29b-41d4-a716-446655440000');
        $tenant = Tenant::create($tenantId, 'Hospital Central', 100);
        $this->tenantRepo->method('findById')->willReturn($tenant);
        $this->userRepo->method('existsByEmailAndTenant')->willReturn(false);
        $this->passwordPolicy->method('validate')->willReturn(
            new \App\Modules\Tenants\Domain\Services\ValidationResult(false, ['Contraseña muy corta']));
        $command = new RegisterUserCommand(
            new Email('nuevo@hospital.com'),
            'Dr. Nuevo',
            'short',
            [Role::MEDICO],
            $tenantId,
            new UserId('550e8400-e29b-41d4-a716-446655440001'),);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Contraseña muy corta');
        $this->useCase->execute($command);
    }
}
