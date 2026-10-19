<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void;
    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?RefreshToken;
    public function findAllByUserId(UserId $userId): array;
    public function delete(RefreshToken $refreshToken): void;
    public function deleteAllByUserId(UserId $userId): void;}
