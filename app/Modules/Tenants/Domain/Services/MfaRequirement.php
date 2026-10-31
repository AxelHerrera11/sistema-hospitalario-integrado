<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
/** Solo se admite TOTP: WebAuthn queda fuera del micro-HIS hasta tener una validación real de atestación. */
final class MfaRequirement
{
    public const TOTP = 'TOTP';
    private bool $required;
    public function __construct(bool $required)
    {
        $this->required = $required;}
    public function required(): bool { return $this->required; }
    public function type(): string { return self::TOTP; }}
