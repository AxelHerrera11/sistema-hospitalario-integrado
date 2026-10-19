<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
final class RotationResult
{
    private bool $success;
    private ?string $accessToken;
    private ?string $refreshToken;
    private ?int $accessExpiresAt;
    private ?int $refreshExpiresAt;
    private ?string $error;
    private bool $reuseDetected;
    private function __construct(
        bool $success,
        ?string $accessToken = null,
        ?string $refreshToken = null,
        ?int $accessExpiresAt = null,
        ?int $refreshExpiresAt = null,
        ?string $error = null,
        bool $reuseDetected = false
    ) {
        $this->success = $success;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->accessExpiresAt = $accessExpiresAt;
        $this->refreshExpiresAt = $refreshExpiresAt;
        $this->error = $error;
        $this->reuseDetected = $reuseDetected;
    }
    public static function success(string $accessToken, string $refreshToken, int $accessExpiresAt, int $refreshExpiresAt): self
    {
        return new self(true, $accessToken, $refreshToken, $accessExpiresAt, $refreshExpiresAt);}
    public static function failure(string $error): self
    {
        return new self(false, error: $error);}
    public static function reuseDetected(string $userId): self
    {
        return new self(false, error: 'Token compromise detected', reuseDetected: true);}
    public function isSuccess(): bool { return $this->success; }
    public function accessToken(): ?string { return $this->accessToken; }
    public function refreshToken(): ?string { return $this->refreshToken; }
    public function accessExpiresAt(): ?int { return $this->accessExpiresAt; }
    public function refreshExpiresAt(): ?int { return $this->refreshExpiresAt; }
    public function error(): ?string { return $this->error; }
    public function isReuseDetected(): bool { return $this->reuseDetected; }}
