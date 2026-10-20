<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Domain;

/** Vigencia [desde, hasta) en formato Y-m-d; hasta = null es abierta. */
final readonly class Period
{
    public function __construct(public string $from, public ?string $to = null)
    {
        if (!self::isDate($from) || ($to !== null && (!self::isDate($to) || $to <= $from))) {
            throw new DoctorRuleViolation('INVALID_PERIOD', 422);
        }
    }

    public static function isDate(string $value): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }

    public function contains(self $other): bool
    {
        return $other->from >= $this->from && ($this->to === null || ($other->to !== null && $other->to <= $this->to));
    }

    public function overlaps(self $other): bool
    {
        return ($other->to === null || $this->from < $other->to) && ($this->to === null || $other->from < $this->to);
    }
}
