<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\Redis;
use Redis;
use RedisException;
interface BlocklistRepositoryInterface
{
    public function add(string $jti, int $expiresAt): void;
    public function exists(string $jti): bool;
    public function remove(string $jti): void;}
final class RedisBlocklistRepository implements BlocklistRepositoryInterface
{
    private Redis $redis;
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;}
    public function add(string $jti, int $expiresAt): void
    {
        $key = "blocklist:{$jti}";
        $ttl = max(1, $expiresAt - time());
        try {
            $this->redis->setex($key, $ttl, '1');
        } catch (RedisException $e) {
            throw new \RuntimeException('Error añadiendo a blocklist: ' . $e->getMessage());}}
    public function exists(string $jti): bool
    {
        $key = "blocklist:{$jti}";
        try {
            return $this->redis->exists($key) === 1;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error verificando blocklist: ' . $e->getMessage());}}
    public function remove(string $jti): void
    {
        $key = "blocklist:{$jti}";
        try {
            $this->redis->del($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error removiendo de blocklist: ' . $e->getMessage());
        }
    }
}
