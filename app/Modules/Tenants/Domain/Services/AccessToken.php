<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
final class AccessToken
{
    private string $token;
    private JwtClaims $claims;
    public function __construct(string $token, JwtClaims $claims)
    {
        $this->token = $token;
        $this->claims = $claims;}
    public function token(): string { return $this->token; }
    public function claims(): JwtClaims { return $this->claims; }
    public function jti(): string { return $this->claims->jti(); }
    public function expiresAt(): int { return $this->claims->expiresAt(); }}
