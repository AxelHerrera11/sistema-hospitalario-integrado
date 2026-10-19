<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\RegisterTenantCommand;
use App\Modules\Tenants\Application\Results\RegisterTenantResult;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class RegisterTenantUseCase
{
    private TenantRepositoryInterface $tenantRepository;
    public function __construct(TenantRepositoryInterface $tenantRepository)
    { $this->tenantRepository = $tenantRepository;}
    public function execute(RegisterTenantCommand $command): RegisterTenantResult
    {
        $tenant = Tenant::create(
            TenantId::generate(),
            $command->name(),
            $command->userQuota(),
            $command->policies());
        $this->tenantRepository->save($tenant);
        return new RegisterTenantResult($tenant->id(), $tenant->name());}}
