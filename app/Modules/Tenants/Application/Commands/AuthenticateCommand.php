<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class AuthenticateCommand
{
    private Email $email;
    private string $rawPassword;
    private TenantId $tenantId;
    private DeviceFingerprint $device;
    private string $ip;
    public function __construct(
        Email $email,
        string $rawPassword,
        TenantId $tenantId,
        DeviceFingerprint $device,
        string $ip
    ) {
        $this->email = $email;
        $this->rawPassword = $rawPassword;
        $this->tenantId = $tenantId;
        $this->device = $device;
        $this->ip = $ip; }
    public function email(): Email { return $this->email; }
    public function rawPassword(): string { return $this->rawPassword; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function ip(): string { return $this->ip; }}
