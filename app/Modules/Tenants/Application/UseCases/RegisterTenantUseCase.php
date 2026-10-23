<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RegisterTenantCommand;
use App\Modules\Tenants\Application\Results\RegisterTenantResult;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class RegisterTenantUseCase
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository,
        private AuditLogRepositoryInterface $auditLog
    ) {}
    public function execute(RegisterTenantCommand $command): RegisterTenantResult
    {
        $tenant = Tenant::create(TenantId::generate(), $command->name(), $command->userQuota(), $command->policies());
        $this->tenantRepository->save($tenant);
        $this->auditLog->log(new DomainEvent(DomainEvent::TENANT_CREATED, ['tenant_id' => $tenant->id()->value(), 'name' => $tenant->name()]));
        return new RegisterTenantResult($tenant->id(), $tenant->name());}}
