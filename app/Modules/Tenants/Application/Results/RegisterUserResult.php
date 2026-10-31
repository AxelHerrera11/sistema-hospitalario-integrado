<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Results;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class RegisterUserResult
{
    private UserId $userId;
    private Email $email;
    private TenantId $tenantId;
    private ?string $totpSecret;
    public function __construct(UserId $userId, Email $email, TenantId $tenantId, ?string $totpSecret = null)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->tenantId = $tenantId;
        $this->totpSecret = $totpSecret;}
    public function userId(): UserId { return $this->userId; }
    public function email(): Email { return $this->email; }
    public function tenantId(): TenantId { return $this->tenantId; }
    /** Secreto TOTP para enrolar la app autenticadora; solo se entrega en el alta. */
    public function totpSecret(): ?string { return $this->totpSecret; }}
