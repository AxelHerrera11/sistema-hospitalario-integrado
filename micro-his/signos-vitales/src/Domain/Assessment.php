<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Resultado de la evaluación: severidad máxima y hallazgos por signo (solo los distintos de None). */
final readonly class Assessment
{
    /** @param array<string, Severity> $findings */
    public function __construct(public Severity $severity, public array $findings)
    {
    }

    public function requiresAlert(): bool
    {
        return $this->severity !== Severity::None;
    }
}
