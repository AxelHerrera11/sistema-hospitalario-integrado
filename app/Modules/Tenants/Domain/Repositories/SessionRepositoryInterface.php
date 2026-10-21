<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
interface SessionRepositoryInterface
{
    public function save(Session $session): void;
    public function findById(SessionId $id): ?Session;
    /** @return Session[] sesiones del usuario en ese hospital, revocadas o no */
    public function findByUser(TenantId $tenantId, UserId $userId): array;
    public function findByUserAndDevice(TenantId $tenantId, UserId $userId, string $deviceId): ?Session;}
