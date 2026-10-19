<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
final class MfaVerificationCommand
{
    private string $challengeId;
    private string $code;
    private string $method;
    public function __construct(string $challengeId, string $code, string $method = 'TOTP')
    {
        $this->challengeId = $challengeId;
        $this->code = $code;
        $this->method = strtoupper($method); }
    public function challengeId(): string { return $this->challengeId; }
    public function code(): string { return $this->code; }
    public function method(): string { return $this->method; }}
