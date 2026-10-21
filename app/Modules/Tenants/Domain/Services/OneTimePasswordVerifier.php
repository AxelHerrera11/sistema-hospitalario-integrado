<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
/** Puerto del segundo factor TOTP (RFC 6238). */
interface OneTimePasswordVerifier
{
    public function generateSecret(): string;
    public function verify(string $secret, string $code, ?int $timestamp = null): bool;}
