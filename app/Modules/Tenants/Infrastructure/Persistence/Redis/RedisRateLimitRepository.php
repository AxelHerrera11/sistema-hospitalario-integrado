<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\Redis;
use Redis;
use RedisException;
final class RedisRateLimitRepository
{
    private Redis $redis;
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;}
    public function incrementAttempts(string $email, string $ip): int
    {
        $key = "login_attempts:{$email}:{$ip}";
        try {
            $count = $this->redis->incr($key);
            if ($count === 1) {
                $this->redis->expire($key, 900); }
            return $count;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error incrementando intentos: ' . $e->getMessage());}}
    public function getAttempts(string $email, string $ip): int
    {
        $key = "login_attempts:{$email}:{$ip}";
        try {
            $val = $this->redis->get($key);
            return $val !== false ? (int)$val : 0;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error obteniendo intentos: ' . $e->getMessage());}}
    public function isRateLimited(string $email, string $ip, int $maxAttempts = 20): bool
    {
        return $this->getAttempts($email, $ip) >= $maxAttempts;}
    public function clearAttempts(string $email, string $ip): void
    {
        $key = "login_attempts:{$email}:{$ip}";
        try {
            $this->redis->del($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error limpiando intentos: ' . $e->getMessage());
        }
    }
}
