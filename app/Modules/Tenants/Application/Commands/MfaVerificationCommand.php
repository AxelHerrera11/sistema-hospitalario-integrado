<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
final class MfaVerificationCommand
{
    private string $challengeId;
    private string $code;
    public function __construct(string $challengeId, string $code)
    {
        $this->challengeId = $challengeId;
        $this->code = $code; }
    public function challengeId(): string { return $this->challengeId; }
    public function code(): string { return $this->code; }}
