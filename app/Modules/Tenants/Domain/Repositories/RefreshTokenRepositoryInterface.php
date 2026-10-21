<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void;
    public function findById(string $id): ?RefreshToken;
    public function revokeBySession(SessionId $sessionId): void;
    /** Revoca solo dentro del hospital indicado: las membresías en otros hospitales no se tocan. */
    public function revokeAllByUser(TenantId $tenantId, UserId $userId): void;}
