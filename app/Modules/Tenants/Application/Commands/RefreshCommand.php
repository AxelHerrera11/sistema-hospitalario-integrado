<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
final class RefreshCommand
{
    private string $refreshToken;
    public function __construct(string $refreshToken)
    { $this->refreshToken = $refreshToken;}
    public function refreshToken(): string { return $this->refreshToken; }}
