<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
final class RefreshCommand
{
    private string $refreshToken;
    private string $ip;
    public function __construct(string $refreshToken, string $ip = 'unknown')
    {
        $this->refreshToken = $refreshToken;
        $this->ip = $ip;}
    public function refreshToken(): string { return $this->refreshToken; }
    public function ip(): string { return $this->ip; }}
