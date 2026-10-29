<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Domain;

use DateTimeImmutable;

/** Periodo `[desde, hasta)` sobre `ended_at`, con desfase explicito en ambos
 * extremos y orden valido: desde < hasta. La comparacion es entre instantes,
 * sin presuponer una zona hospitalaria local. */
final class Period
{
    private function __construct(
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $until,
    ) {
    }

    /** @param string $from Instante ISO-8601 obligatorio con offset (ej.: 2026-01-01T00:00:00-06:00). */
    public static function create(string $from, string $until): self
    {
        preg_match('/[+-]\d{2}:\d{2}$/', $from) === 1
            || preg_match('/[+-]\d{2}:\d{2}$/', $until) === 1
            || throw new IndicatorViolation('periodo_invalido');
        $start = self::instant($from);
        $end = self::instant($until);
        $end <= $start && throw new IndicatorViolation('periodo_invalido');

        return new self($start, $end);
    }

    private static function instant(string $value): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP', $value)
            ?: DateTimeImmutable::createFromFormat('!Y-m-d H:i:sP', $value);
        $parsed === false && throw new IndicatorViolation('periodo_invalido');

        return $parsed;
    }

    /** LÃ­mite inferior inclusivo en formato comparable (ISO-8601 con offset). */
    public function lower(): string
    {
        return $this->from->format('Y-m-d\TH:i:sP');
    }

    /** LÃ­mite superior exclusivo. */
    public function upper(): string
    {
        return $this->until->format('Y-m-d\TH:i:sP');
    }
}

