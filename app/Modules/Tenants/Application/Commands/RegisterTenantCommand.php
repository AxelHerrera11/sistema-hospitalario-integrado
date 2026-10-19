<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class RegisterTenantCommand
{
    private string $name;
    private int $userQuota;
    private array $policies;
    public function __construct(string $name, int $userQuota = 100, array $policies = [])
    {
        $this->name = $name;
        $this->userQuota = $userQuota;
        $this->policies = $policies;}
    public function name(): string { return $this->name; }
    public function userQuota(): int { return $this->userQuota; }
    public function policies(): array { return $this->policies; }}
