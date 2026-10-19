<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class InMemoryRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private array $tokens = [];
    private array $userTokensIndex = [];
    public function save(RefreshToken $refreshToken): void
    {
        $key = $this->getKey($refreshToken->userId()->value(), $refreshToken->device()->deviceId());
        $this->tokens[$key] = $refreshToken;
        $userId = $refreshToken->userId()->value();
        if (!isset($this->userTokensIndex[$userId])) {
            $this->userTokensIndex[$userId] = [];}
        if (!in_array($key, $this->userTokensIndex[$userId], true)) {
            $this->userTokensIndex[$userId][] = $key;}}
    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?RefreshToken
    {
        $key = $this->getKey($userId->value(), $deviceId);
        return $this->tokens[$key] ?? null;}
    public function findAllByUserId(UserId $userId): array
    {
        $keys = $this->userTokensIndex[$userId->value()] ?? [];
        $tokens = [];
        foreach ($keys as $key) {
            if (isset($this->tokens[$key])) {
                $tokens[] = $this->tokens[$key];}}
        return $tokens;}
    public function delete(RefreshToken $refreshToken): void
    {
        $key = $this->getKey($refreshToken->userId()->value(), $refreshToken->device()->deviceId());
        unset($this->tokens[$key]);
        $userId = $refreshToken->userId()->value();
        $this->userTokensIndex[$userId] = array_filter(
            $this->userTokensIndex[$userId] ?? [],
            fn(string $k) => $k !== $key);}
    public function deleteAllByUserId(UserId $userId): void
    {
        $keys = $this->userTokensIndex[$userId->value()] ?? [];
        foreach ($keys as $key) {
            unset($this->tokens[$key]);}
        unset($this->userTokensIndex[$userId->value()]);}
    public function findByTokenPrefix(string $tokenPrefix): ?RefreshToken
    {
        foreach ($this->tokens as $token) {
            if (str_starts_with($token->token(), $tokenPrefix)) {
                return $token;}}
        return null;}
    public function clear(): void
    {
        $this->tokens = [];
        $this->userTokensIndex = [];}
    private function getKey(string $userId, string $deviceId): string
    {
        return "refresh:{$userId}:{$deviceId}";
    }
}
