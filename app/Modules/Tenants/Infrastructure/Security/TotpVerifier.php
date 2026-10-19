<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Security;
use OTPHP\TOTP;
final class TotpVerifier
{
    private int $window;
    public function __construct(int $window = 1)
    {
        $this->window = $window;
    }
    public function verify(string $secret, string $code): bool
    {
        if (empty($secret) || empty($code)) {
            return false;
        }
        try {
            $totp = TOTP::create($secret);
            $totp->setWindow($this->window);
            return $totp->verify($code);
        } catch (\Exception) {
            return false;
        }
    }
    public function generateSecret(): string
    {
        $totp = TOTP::create();
        return $totp->getSecret();
    }
    public function getProvisioningUri(string $secret, string $accountName, string $issuer = 'HIS'): string
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($accountName);
        $totp->setIssuer($issuer);
        return $totp->getProvisioningUri();
    }
}
