<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RegisterUserCommand;
use App\Modules\Tenants\Application\Results\RegisterUserResult;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\OneTimePasswordVerifier;
use App\Modules\Tenants\Domain\Services\PasswordPolicy;
use DomainException;
final class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TenantRepositoryInterface $tenantRepository,
        private PasswordPolicy $passwordPolicy,
        private MfaPolicy $mfaPolicy,
        private OneTimePasswordVerifier $otpVerifier,
        private AuditLogRepositoryInterface $auditLog
    ) {}
    public function execute(RegisterUserCommand $command): RegisterUserResult
    {
        $tenantId = $command->tenantId();
        $tenant = $this->tenantRepository->findById($tenantId);
        if ($tenant === null) {
            throw new DomainException('Hospital no encontrado', 404);}
        if (!$tenant->isActive()) {
            $this->auditLog->log(new DomainEvent(DomainEvent::TENANT_INACTIVE, ['tenant_id' => $tenantId->value(), 'user_id' => $command->createdBy()->value()]));
            throw new DomainException('Hospital inactivo', 403);}
        if (!$tenant->hasQuota()) {
            $this->auditLog->log(new DomainEvent(DomainEvent::TENANT_QUOTA_EXCEEDED, ['tenant_id' => $tenantId->value(), 'current_count' => $tenant->usersCount(), 'quota' => $tenant->userQuota()]));
            throw new DomainException('Cupo de usuarios excedido', 409);}
        $existingId = $command->existingUserId();
        if ($this->userRepository->existsByEmailAndTenant($command->email(), $tenantId)
            || ($existingId !== null && $this->userRepository->findByIdAndTenant($existingId, $tenantId) !== null)) {
            throw new DomainException('El usuario ya tiene membresía en este hospital', 409);}
        $validation = $this->passwordPolicy->validate($command->rawPassword());
        if (!$validation->isValid()) {
            throw new \InvalidArgumentException((string) $validation->firstError());}
        $mfaRequired = $this->mfaPolicy->evaluate($command->roles(), $tenant->policies(), $command->mfaRequired())->required();
        $totpSecret = $mfaRequired ? $this->otpVerifier->generateSecret() : null;
        $user = User::create(
            $command->email(),
            $this->passwordPolicy->hash($command->rawPassword()),
            $command->name(),
            $command->roles(),
            $tenantId,
            $totpSecret,
            $mfaRequired,
            $existingId);
        $this->userRepository->save($user);
        $tenant->incrementUsersCount();
        $this->tenantRepository->save($tenant);
        $this->auditLog->log(new DomainEvent(DomainEvent::USER_CREATED, [
            'tenant_id' => $tenantId->value(),
            'user_id' => $user->id()->value(),
            'email' => $user->email()->value(),
            'roles' => $user->roleValues(),
            'created_by' => $command->createdBy()->value(),]));
        return new RegisterUserResult($user->id(), $user->email(), $tenantId, $totpSecret);}}
