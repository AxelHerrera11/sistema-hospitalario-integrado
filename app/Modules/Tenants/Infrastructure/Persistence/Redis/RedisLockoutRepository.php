<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\Redis;
use Redis;
use RedisException;
final class RedisLockoutRepository
{
    private Redis $redis;
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;}
    public function incrementLoginFailures(string $userId): int
    {
        $key = "login_failures:{$userId}";
        try {
            $count = $this->redis->incr($key);
            if ($count === 1) {
                $this->redis->expire($key, 900); } return $count;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error incrementando fallos login: ' . $e->getMessage());}}
    public function getLoginFailures(string $userId): int
    {
        $key = "login_failures:{$userId}";
        try {
            $val = $this->redis->get($key);
            return $val !== false ? (int)$val : 0;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error obteniendo fallos login: ' . $e->getMessage());}}
    public function clearLoginFailures(string $userId): void
    {
        $key = "login_failures:{$userId}";
        try {
            $this->redis->del($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error limpiando fallos login: ' . $e->getMessage());}}
    public function setAccountLock(string $userId, int $ttl): void
    {
        $key = "account_lock:{$userId}";
        try {
            $this->redis->setex($key, $ttl, 'locked');
        } catch (RedisException $e) {
            throw new \RuntimeException('Error estableciendo bloqueo cuenta: ' . $e->getMessage());}}
    public function isAccountLocked(string $userId): bool
    {
        $key = "account_lock:{$userId}";
        try {
            return $this->redis->exists($key) === 1;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error verificando bloqueo cuenta: ' . $e->getMessage());}}
    public function incrementMfaFailures(string $userId): int
    {
        $key = "mfa_failures:{$userId}";
        try {
            $count = $this->redis->incr($key);
            if ($count === 1) {
                $this->redis->expire($key, 1800);
            }
            return $count;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error incrementando fallos MFA: ' . $e->getMessage());}}
    public function getMfaFailures(string $userId): int
    {
        $key = "mfa_failures:{$userId}";
        try {
            $val = $this->redis->get($key);
            return $val !== false ? (int)$val : 0;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error obteniendo fallos MFA: ' . $e->getMessage());}}
    public function clearMfaFailures(string $userId): void
    {
        $key = "mfa_failures:{$userId}";
        try {
            $this->redis->del($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error limpiando fallos MFA: ' . $e->getMessage());}}
    public function setMfaLock(string $userId, int $ttl): void
    {
        $key = "mfa_lock:{$userId}";
        try {
            $this->redis->setex($key, $ttl, 'locked');
        } catch (RedisException $e) {
            throw new \RuntimeException('Error estableciendo bloqueo MFA: ' . $e->getMessage());}}
    public function isMfaLocked(string $userId): bool
    {
        $key = "mfa_lock:{$userId}";
        try {
            return $this->redis->exists($key) === 1;
        } catch (RedisException $e) {
            throw new \RuntimeException('Error verificando bloqueo MFA: ' . $e->getMessage());}}}
