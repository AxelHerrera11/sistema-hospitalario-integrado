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
        (preg_match('/[+-]\d{2}:\d{2}$/', $from) === 1 && preg_match('/[+-]\d{2}:\d{2}$/', $until) === 1)
            || throw new IndicatorViolation('periodo_invalido');
        $start = self::instant($from);
        $end = self::instant($until);
        $end <= $start && throw new IndicatorViolation('periodo_invalido');

        return new self($start, $end);
    }

    private static function instant(string $value): DateTimeImmutable
    {
        foreach (['Y-m-d\TH:i:sP', 'Y-m-d H:i:sP'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!' . $format, $value);
            // Sin esta comparación, PHP desborda 2026-02-31 a marzo y lo acepta como fecha válida.
            if ($parsed !== false && $parsed->format($format) === $value) {
                return $parsed;
            }
        }

        throw new IndicatorViolation('periodo_invalido');
    }

    /** Límite inferior inclusivo en formato comparable (ISO-8601 con offset). */
    public function lower(): string
    {
        return $this->from->format('Y-m-d\TH:i:sP');
    }

    /** Límite superior exclusivo. */
    public function upper(): string
    {
        return $this->until->format('Y-m-d\TH:i:sP');
    }
}

