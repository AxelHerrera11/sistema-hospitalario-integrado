<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** Hallazgo de una regla del gate. severity: blocker | rejection | warning. */
final readonly class GateFinding
{
    public const SEVERITIES = ['blocker', 'rejection', 'warning'];

    public function __construct(
        public string $rule,
        public string $severity,
        public ?string $suite,
        public string $detail,
    ) {
        if (! in_array($severity, self::SEVERITIES, true)) {
            throw new \InvalidArgumentException("Severidad desconocida: {$severity}");
        }
    }

    /** @return array{rule: string, severity: string, suite: ?string, detail: string} */
    public function toArray(): array
    {
        return ['rule' => $this->rule, 'severity' => $this->severity, 'suite' => $this->suite, 'detail' => $this->detail];
    }
}
