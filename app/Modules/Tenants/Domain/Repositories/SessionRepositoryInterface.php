<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
interface SessionRepositoryInterface
{
    public function save(Session $session): void;
    public function findById(SessionId $id): ?Session;
    public function findByUserId(UserId $userId): array;
    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?Session;
    public function findAllByUserId(UserId $userId): array;
    public function delete(SessionId $id): void;
    public function deleteAllByUserId(UserId $userId): void;
    public function findIdleSessions(int $idleTimeoutSeconds): array;}
