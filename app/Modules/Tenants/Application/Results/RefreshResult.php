<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Results;
use App\Modules\Tenants\Domain\Services\AccessToken;
final class RefreshResult
{
    private AccessToken $accessToken;
    private string $refreshToken;
    private int $expiresIn;
    public function __construct(AccessToken $accessToken, string $refreshToken, int $expiresIn)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;}
    public function accessToken(): AccessToken { return $this->accessToken; }
    public function refreshToken(): string { return $this->refreshToken; }
    public function expiresIn(): int { return $this->expiresIn; }}
