<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
final class MfaRequirement
{
    private bool $required;
    private string $type;
    public function __construct(bool $required, string $type = 'TOTP')
    {
        $this->required = $required;
        $this->type = strtoupper($type);}
    public function required(): bool { return $this->required; }
    public function type(): string { return $this->type; }
    public function isTotp(): bool { return $this->type === 'TOTP'; }
    public function isWebAuthn(): bool { return $this->type === 'WEBAUTHN'; }}
