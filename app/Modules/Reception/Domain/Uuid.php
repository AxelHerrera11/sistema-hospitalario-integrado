<?php

declare(strict_types=1);

namespace App\Modules\Reception\Domain;

final class Uuid
{
    public static function generate(): string
    {
        $hex = bin2hex(random_bytes(16));
        $hex[12] = '4';
        $hex[16] = dechex((hexdec($hex[16]) & 3) | 8);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
