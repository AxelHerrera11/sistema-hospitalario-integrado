<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Security;
use App\Modules\Tenants\Domain\Services\OneTimePasswordVerifier;
/** TOTP según RFC 6238 (HMAC-SHA1, 6 dígitos, paso de 30 s) sin dependencias externas. */
final class TotpVerifier implements OneTimePasswordVerifier
{
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private int $window;
    private int $period;
    private int $digits;
    public function __construct(int $window = 1, int $period = 30, int $digits = 6)
    {
        $this->window = $window;
        $this->period = $period;
        $this->digits = $digits;}
    public function generateSecret(): string
    {
        $secret = '';
        foreach (str_split(random_bytes(20)) as $byte) {
            $secret .= sprintf('%08b', ord($byte));}
        return implode('', array_map(fn(string $bits) => self::BASE32[bindec($bits)], str_split($secret, 5)));}
    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        if ($secret === '' || !preg_match('/^\d{' . $this->digits . '}$/', $code)) {
            return false;}
        $counter = intdiv($timestamp ?? time(), $this->period);
        for ($offset = -$this->window; $offset <= $this->window; $offset++) {
            if (hash_equals($this->codeAt($secret, $counter + $offset), $code)) {
                return true;}}
        return false;}
    public function codeAt(string $secret, int $counter): string
    {
        $hmac = hash_hmac('sha1', pack('J', $counter), self::base32Decode($secret), true);
        $offset = ord($hmac[19]) & 0x0F;
        $binary = unpack('N', substr($hmac, $offset, 4))[1] & 0x7FFFFFFF;
        return str_pad((string) ($binary % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);}
    public function provisioningUri(string $secret, string $accountName, string $issuer = 'HIS'): string
    {
        return sprintf('otpauth://totp/%s:%s?secret=%s&issuer=%s', rawurlencode($issuer), rawurlencode($accountName), $secret, rawurlencode($issuer));}
    private static function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($secret, '='))) as $char) {
            $value = strpos(self::BASE32, $char);
            if ($value === false) {
                throw new \InvalidArgumentException('Secreto TOTP no es Base32');}
            $bits .= sprintf('%05b', $value);}
        return implode('', array_map(fn(string $byte) => chr(bindec($byte)), str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8)));}}
