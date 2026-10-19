<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Results;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class RegisterUserResult
{
    private UserId $userId;
    private Email $email;
    private string $tempPassword;
    private TenantId $tenantId;
    public function __construct(UserId $userId, Email $email, string $tempPassword, TenantId $tenantId)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->tempPassword = $tempPassword;
        $this->tenantId = $tenantId;}
    public function userId(): UserId { return $this->userId; }
    public function email(): Email { return $this->email; }
    public function tempPassword(): string { return $this->tempPassword; }
    public function tenantId(): TenantId { return $this->tenantId; }}
