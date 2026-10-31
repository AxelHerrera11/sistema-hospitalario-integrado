<?php

declare(strict_types=1);

namespace Reception\Domain;

final class SpecimenCode
{
    public static function generate(): string
    {
        $body = strtoupper(bin2hex(random_bytes(12)));

        return $body.'-'.self::digit($body);
    }

    public static function valid(string $code): bool
    {
        return preg_match('/^[A-F0-9]{24}-[0-9]$/D', $code) === 1
            && self::digit(substr($code, 0, 24)) === substr($code, -1);
    }

    private static function digit(string $body): string
    {
        $sum = 0;
        foreach (str_split($body) as $char) {
            $sum += hexdec($char);
        }

        return (string) ($sum % 10);
    }
}
