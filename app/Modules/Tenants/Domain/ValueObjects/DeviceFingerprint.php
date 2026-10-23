<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
final class DeviceFingerprint
{
    private string $deviceId;
    private string $ip;
    private string $userAgent;
    private int $trustScore;
    private bool $isKnown;
    public function __construct(
        string $deviceId,
        string $ip,
        string $userAgent,
        int $trustScore = 0,
        bool $isKnown = false) {
        if (empty($deviceId)) throw new InvalidArgumentException('Device ID requerido');
        if (empty($ip)) throw new InvalidArgumentException('IP requerida');
        if (empty($userAgent)) throw new InvalidArgumentException('User Agent requerido');
        if ($trustScore < 0 || $trustScore > 100) throw new InvalidArgumentException('Trust score debe ser 0-100');
        $this->deviceId = $deviceId;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->trustScore = $trustScore;
        $this->isKnown = $isKnown;}
    public static function createNew(string $deviceId, string $ip, string $userAgent): self
    {
        return new self($deviceId, $ip, $userAgent, 0, false);}
    public static function createKnown(string $deviceId, string $ip, string $userAgent, int $trustScore): self
    {
        return new self($deviceId, $ip, $userAgent, $trustScore, true);}
    public function deviceId(): string { return $this->deviceId; }
    public function ip(): string { return $this->ip; }
    public function userAgent(): string { return $this->userAgent; }
    public function trustScore(): int { return $this->trustScore; }
    public function isKnown(): bool { return $this->isKnown; }
    public function withTrustScore(int $score): self
    {
        return new self($this->deviceId, $this->ip, $this->userAgent, $score, true);}
    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'trust_score' => $this->trustScore,
            'is_known' => $this->isKnown,];}}
