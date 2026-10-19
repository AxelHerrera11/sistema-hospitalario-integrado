<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\Redis;
use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use Redis;
use RedisException;
final class RedisSessionRepository implements SessionRepositoryInterface
{
    private Redis $redis;
    private int $defaultTtl;
    public function __construct(Redis $redis, int $defaultTtl = 604800)
    {
        $this->redis = $redis;
        $this->defaultTtl = $defaultTtl;}
    public function save(Session $session): void
    {
        $key = "session:{$session->id()->value()}";
        $data = json_encode([
            'session_id' => $session->id()->value(),
            'user_id' => $session->userId()->value(),
            'tenant_id' => $session->tenantId()->value(),
            'device' => $session->device()->toArray(),
            'created_at' => $session->createdAt()->format('c'),
            'last_activity_at' => $session->lastActivityAt()->format('c'),
            'revoked' => $session->isRevoked(),]);
        try {
            $this->redis->setex($key, $this->defaultTtl, $data);
            $userKey = "session:user:{$session->userId()->value()}";
            $this->redis->sadd($userKey, $session->id()->value());
            $this->redis->expire($userKey, $this->defaultTtl);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error guardando sesión: ' . $e->getMessage());}}
    public function findById(SessionId $id): ?Session
    {
        $key = "session:{$id->value()}";
        try {
            $data = $this->redis->get($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error buscando sesión: ' . $e->getMessage());}
        if ($data === false) { return null;}
        return $this->mapDataToSession(json_decode($data, true));}
    public function findByUserId(UserId $userId): array
    {
        $userKey = "session:user:{$userId->value()}";
        try {
            $sessionIds = $this->redis->smembers($userKey);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error listando sesiones de usuario: ' . $e->getMessage());}
        $sessions = [];
        foreach ($sessionIds as $sid) {
            $session = $this->findById(SessionId::fromString($sid));
            if ($session !== null) {
                $sessions[] = $session;}} return $sessions;}
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
        $key = "session:{$id->value()}";
        try {
            $data = $this->redis->get($key);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                $userKey = "session:user:{$decoded['user_id']}";
                $this->redis->srem($userKey, $id->value());}
            $this->redis->del($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error eliminando sesión: ' . $e->getMessage());}}
    public function deleteAllByUserId(UserId $userId): void
    {
        $userKey = "session:user:{$userId->value()}";
        try {
            $sessionIds = $this->redis->smembers($userKey);
            foreach ($sessionIds as $sid) {
                $this->redis->del("session:{$sid}");}
            $this->redis->del($userKey);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error eliminando sesiones de usuario: ' . $e->getMessage());}}
    public function findIdleSessions(int $idleTimeoutSeconds): array
    {
        $threshold = time() - $idleTimeoutSeconds;
        try {
            $keys = $this->redis->keys('session:*');
        } catch (RedisException $e) {
            throw new \RuntimeException('Error escaneando sesiones idle: ' . $e->getMessage());}
        $idleSessions = [];
        foreach ($keys as $key) {
            if (str_starts_with($key, 'session:user:')) continue;
            $data = $this->redis->get($key);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                $lastActivity = strtotime($decoded['last_activity_at']);
                if ($lastActivity < $threshold && !$decoded['revoked']) {
                    $idleSessions[] = $this->mapDataToSession($decoded);}}}
        return $idleSessions;}
    private function mapDataToSession(array $data): Session
    {
        $device = new DeviceFingerprint(
            $data['device']['device_id'],
            $data['device']['ip'],
            $data['device']['user_agent'],
            $data['device']['trust_score'],
            $data['device']['is_known']);
        return Session::reconstitute(
            SessionId::fromString($data['session_id']),
            UserId::fromString($data['user_id']),
            TenantId::fromString($data['tenant_id']),
            $device,
            new \DateTimeImmutable($data['created_at']),
            new \DateTimeImmutable($data['last_activity_at']),
            (bool)$data['revoked']);}}
