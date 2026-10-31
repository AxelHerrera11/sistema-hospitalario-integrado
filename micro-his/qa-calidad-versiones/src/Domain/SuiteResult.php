<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** Resultado observado de una suite del plan con sus evidencias. */
final readonly class SuiteResult
{
    /** @param list<EvidenceRef> $evidence */
    public function __construct(
        public string $suite,
        public int $total,
        public int $failed,
        public int $skipped,
        public int $durationMs,
        public array $evidence = [],
    ) {
        if ($total < 0 || $failed < 0 || $skipped < 0 || $durationMs < 0) {
            throw new InvalidQaInput('conteo_negativo', 'Los conteos y la duración no pueden ser negativos.');
        }
        if ($failed + $skipped > $total) {
            throw new InvalidQaInput('conteo_inconsistente', 'Fallidas más omitidas no puede superar el total.');
        }
    }

    public function passed(): int
    {
        return $this->total - $this->failed - $this->skipped;
    }

    public function executed(): int
    {
        return $this->total - $this->skipped;
    }

    /**
     * Proporción aprobada sobre lo EJECUTADO; las omitidas tienen su propia regla
     * (SKIPPED_IN_REQUIRED). Sin pruebas ejecutadas no hay tasa (null).
     */
    public function passRate(): ?float
    {
        return $this->executed() === 0 ? null : $this->passed() / $this->executed();
    }
}
