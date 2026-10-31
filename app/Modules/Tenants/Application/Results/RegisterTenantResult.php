<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Results;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class RegisterTenantResult
{
    private TenantId $tenantId;
    private string $name;
    public function __construct(TenantId $tenantId, string $name)
    {
        $this->tenantId = $tenantId;
        $this->name = $name;}
    public function tenantId(): TenantId { return $this->tenantId; }
    public function name(): string { return $this->name; }}
