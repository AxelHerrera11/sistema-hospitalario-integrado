<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
final class RefreshTokenResult
{
    private string $token;
    private int $expiresAt;
    public function __construct(string $token, int $expiresAt)
    {
        $this->token = $token;
        $this->expiresAt = $expiresAt;}
    public function token(): string { return $this->token; }
    public function expiresAt(): int { return $this->expiresAt; }}
