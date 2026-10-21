<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class InMemorySessionRepository implements SessionRepositoryInterface
{
    /** @var array<string, Session> */
    private array $sessions = [];
    public function save(Session $session): void
    {
        $this->sessions[$session->id()->value()] = $session;}
    public function findById(SessionId $id): ?Session
    {
        return $this->sessions[$id->value()] ?? null;}
    public function findByUser(TenantId $tenantId, UserId $userId): array
    {
        return array_values(array_filter($this->sessions, fn(Session $s) => $s->belongsTo($tenantId, $userId)));}
    public function findByUserAndDevice(TenantId $tenantId, UserId $userId, string $deviceId): ?Session
    {
        foreach ($this->findByUser($tenantId, $userId) as $session) {
            if ($session->device()->deviceId() === $deviceId) {
                return $session;}}
        return null;}}
