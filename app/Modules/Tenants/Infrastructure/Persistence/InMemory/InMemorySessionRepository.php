<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class InMemorySessionRepository implements SessionRepositoryInterface
{
    private array $sessions = [];
    private array $userSessionsIndex = [];
    public function save(Session $session): void
    {
        $this->sessions[$session->id()->value()] = $session;
        $userId = $session->userId()->value();
        if (!isset($this->userSessionsIndex[$userId])) {
            $this->userSessionsIndex[$userId] = [];}
        if (!in_array($session->id()->value(), $this->userSessionsIndex[$userId], true)) {
            $this->userSessionsIndex[$userId][] = $session->id()->value();}}
    public function findById(SessionId $id): ?Session
    {
        return $this->sessions[$id->value()] ?? null;}
    public function findByUserId(UserId $userId): array
    {
        $sessionIds = $this->userSessionsIndex[$userId->value()] ?? [];
        $sessions = [];
        foreach ($sessionIds as $sid) {
            if (isset($this->sessions[$sid])) {
                $sessions[] = $this->sessions[$sid];}}
        return $sessions;}
    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?Session
    {
        $sessions = $this->findByUserId($userId);
        foreach ($sessions as $session) {
            if ($session->device()->deviceId() === $deviceId) {
                return $session;}}
        return null;}
    public function findAllByUserId(UserId $userId): array
    {
        return $this->findByUserId($userId);}
    public function delete(SessionId $id): void
    {
        $session = $this->sessions[$id->value()] ?? null;
        if ($session !== null) {
            $userId = $session->userId()->value();
            $this->userSessionsIndex[$userId] = array_filter(
                $this->userSessionsIndex[$userId] ?? [],
                fn(string $sid) => $sid !== $id->value());
            unset($this->sessions[$id->value()]);}}
    public function deleteAllByUserId(UserId $userId): void
    {
        $sessionIds = $this->userSessionsIndex[$userId->value()] ?? [];
        foreach ($sessionIds as $sid) {
            unset($this->sessions[$sid]);}
        unset($this->userSessionsIndex[$userId->value()]);}
    public function findIdleSessions(int $idleTimeoutSeconds): array
    {
        $threshold = time() - $idleTimeoutSeconds;
        $idle = [];
        foreach ($this->sessions as $session) {
            if (!$session->isRevoked() && $session->lastActivityAt()->getTimestamp() < $threshold) {
                $idle[] = $session;
            }}
        return $idle;}
    public function clear(): void
    {
        $this->sessions = [];
        $this->userSessionsIndex = [];
    }
}
