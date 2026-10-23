<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Domain;

/** Versión de una prueba del catálogo. Nace válida o no nace (UC-LAB-01 a 03). */
final readonly class LabTest
{
    private function __construct(
        public string $id, public string $code, public string $name, public string $specimenType,
        public float $minVolumeMl, public string $resultType, public ?string $unit, public ?float $referenceLow,
        public ?float $referenceHigh, public ?float $criticalLow, public ?float $criticalHigh,
        public int $versionNo, public string $validFrom,
    ) {}

    /** @param array<string, mixed> $d */
    public static function register(string $id, array $d, int $versionNo, string $today): self
    {
        foreach (['code', 'name', 'specimen_type', 'result_type', 'valid_from'] as $field) {
            if (!is_string($d[$field] ?? null) || trim($d[$field]) === '') {
                throw new DomainRuleViolation('FIELD_REQUIRED', $field);
            }
        }
        $num = static fn (string $k): ?float => is_numeric($d[$k] ?? null) ? (float) $d[$k] : null;
        $test = new self($id, strtoupper(trim($d['code'])), trim($d['name']), $d['specimen_type'], $num('min_volume_ml') ?? 0.0,
            $d['result_type'], $d['unit'] ?? null, $num('reference_low'), $num('reference_high'), $num('critical_low'),
            $num('critical_high'), $versionNo, $d['valid_from']);
        $test->assertRules($today);

        return $test;
    }

    private function assertRules(string $today): void
    {
        $ranges = [$this->referenceLow, $this->referenceHigh, $this->criticalLow, $this->criticalHigh];
        $rule = match (true) {
            $this->minVolumeMl <= 0 => ['SPECIMEN_REQUIREMENT_INVALID', 'min_volume_ml'],
            !in_array($this->resultType, ['numeric', 'qualitative', 'text'], true) => ['RESULT_TYPE_UNSUPPORTED', 'result_type'],
            $this->resultType === 'numeric' && ($this->unit === null || in_array(null, $ranges, true)) => ['RANGES_INCOHERENT', 'unit'],
            $this->resultType === 'numeric' && !($this->criticalLow < $this->referenceLow && $this->referenceLow <= $this->referenceHigh
                && $this->referenceHigh < $this->criticalHigh) => ['RANGES_INCOHERENT', 'reference_low'],
            $this->resultType !== 'numeric' && array_filter($ranges, 'is_float') !== [] => ['RESULT_DEFINITION_INVALID', 'result_type'],
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->validFrom) || $this->validFrom < $today => ['VALIDITY_INVALID', 'valid_from'],
            default => null,
        };
        if ($rule !== null) {
            throw new DomainRuleViolation(...$rule);
        }
    }
}
