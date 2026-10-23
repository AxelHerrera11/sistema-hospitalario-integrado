<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * Umbrales del gate. Los valores por defecto son una PROPUESTA pendiente de
 * aprobación del grupo (ver docs/CI-E2E/semana-14); se cargan desde
 * config/quality-gate.json para que cambiarlos no requiera tocar código.
 */
final readonly class QualityGatePolicy
{
    /** @param array<string, float> $minPassRate tipo de suite => proporción mínima [0, 1] */
    public function __construct(
        public string $version,
        public array $minPassRate,
        public bool $requireEvidence,
        public bool $allowSkippedInRequired,
        public ?int $maxE2eDurationMs,
    ) {
        if (preg_match('/^[0-9A-Za-z._-]{1,40}$/', $version) !== 1) {
            throw new InvalidQaInput('politica_version_invalida', 'La política necesita una versión identificable.');
        }
        foreach ($minPassRate as $kind => $rate) {
            SuiteKind::parse($kind);
            if ((! is_float($rate) && ! is_int($rate)) || $rate < 0 || $rate > 1) {
                throw new InvalidQaInput('umbral_invalido', "El umbral de {$kind} debe estar entre 0 y 1.");
            }
        }
        if ($maxE2eDurationMs !== null && $maxE2eDurationMs < 1) {
            throw new InvalidQaInput('umbral_invalido', 'La duración máxima E2E debe ser positiva.');
        }
    }

    public static function fromArray(array $data): self
    {
        $rates = [];
        foreach (($data['min_pass_rate'] ?? []) as $kind => $rate) {
            $rates[(string) $kind] = is_numeric($rate) ? (float) $rate : -1.0;
        }

        return new self(
            (string) ($data['version'] ?? ''),
            $rates,
            (bool) ($data['require_evidence'] ?? true),
            (bool) ($data['allow_skipped_in_required'] ?? false),
            isset($data['max_e2e_duration_ms']) ? (int) $data['max_e2e_duration_ms'] : null,
        );
    }

    public function minPassRateFor(SuiteKind $kind): float
    {
        return (float) ($this->minPassRate[$kind->value] ?? 1.0);
    }
}
