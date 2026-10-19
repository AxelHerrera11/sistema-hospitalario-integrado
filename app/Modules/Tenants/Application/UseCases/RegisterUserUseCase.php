<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RegisterUserCommand;
use App\Modules\Tenants\Application\Results\RegisterUserResult;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Events\UserCreated;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\PasswordPolicy;
use App\Modules\Tenants\Domain\Services\MfaRequirement;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Password;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\EmailNotificationService;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
final class RegisterUserUseCase
{
    private UserRepositoryInterface $userRepository;
    private TenantRepositoryInterface $tenantRepository;
    private PasswordPolicy $passwordPolicy;
    private MfaPolicy $mfaPolicy;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    private EmailNotificationService $emailService;
    public function __construct(
        UserRepositoryInterface $userRepository,
        TenantRepositoryInterface $tenantRepository,
        PasswordPolicy $passwordPolicy,
        MfaPolicy $mfaPolicy,
        AuditLogRepositoryInterface $auditLog,
        EventPublisher $eventPublisher,
        EmailNotificationService $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->tenantRepository = $tenantRepository;
        $this->passwordPolicy = $passwordPolicy;
        $this->mfaPolicy = $mfaPolicy;
        $this->auditLog = $auditLog;
        $this->eventPublisher = $eventPublisher;
        $this->emailService = $emailService;}
    public function execute(RegisterUserCommand $command): RegisterUserResult
    {
        $tenant = $this->tenantRepository->findById($command->tenantId());
        if ($tenant === null) {
            throw new \DomainException('Tenant no encontrado');}
        if (!$tenant->isActive()) {
            $this->auditLog->log(new \App\Modules\Tenants\Domain\Events\TenantInactive(
                $command->tenantId(),
                $command->createdBy(),
                'unknown'));
            throw new \DomainException('Tenant inactivo');}
        if (!$tenant->hasQuota()) {
            $this->auditLog->log(new \App\Modules\Tenants\Domain\Events\TenantQuotaExceeded(
                $command->tenantId(),
                $tenant->usersCount(),
                $tenant->userQuota()));
            throw new \DomainException('Cupo de usuarios excedido');}
        if ($this->userRepository->existsByEmailAndTenant($command->email(), $command->tenantId())) {
            throw new \DomainException('Email ya registrado en este tenant');}
        $validation = $this->passwordPolicy->validate($command->rawPassword());
        if (!$validation->isValid()) {
            throw new \DomainException($validation->firstError());}
        $hasher = new Argon2idHasher();
        $password = $passwordPolicy->hash($command->rawPassword());
        $mfaRequirement = $this->mfaPolicy->evaluate(
            $command->roles(),
            $tenant->policies(),
            $command->mfaRequired());
        $user = User::create(
            $command->email(),
            $password,
            $command->name(),
            array_map(fn(Role $r) => $r->value(), $command->roles()),
            $command->tenantId(),
            null,
            $mfaRequirement->required());
        $tempPassword = $this->generateTempPassword();
        $activationToken = bin2hex(random_bytes(32));
        $this->userRepository->save($user);
        $tenant->incrementUsersCount();
        $this->tenantRepository->save($tenant);
        $this->auditLog->log(new UserCreated(
            $user->id(),
            $user->tenantId(),
            $user->email()->value(),
            $user->roleValues(),
            $command->createdBy()));
        $this->eventPublisher->publish($userCreatedEvent = new UserCreated(
            $user->id(),
            $user->tenantId(),
            $user->email()->value(),
            $user->roleValues(),
            $command->createdBy()));
        $this->emailService->sendActivationEmail(
            $user->email()->value(),
            $user->name(),
            $tempPassword,
            $activationToken);
        return new RegisterUserResult(
            $user->id(),
            $user->email(),
            $tempPassword,
            $user->tenantId());}
    private function generateTempPassword(): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
        $password = '';
        for ($i = 0; $i < 16; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];} return $password;}}
