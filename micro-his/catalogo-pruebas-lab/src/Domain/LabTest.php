<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Domain;

/** Versión de una prueba del catálogo. Nace válida o no nace (UC-LAB-01 a 03). */
final readonly class LabTest
{
    /** Nombre igual al maxlength de la pantalla; la API no confía en el cliente. */
    public const MAX_LENGTH = ['name' => 160, 'specimen_type' => 40, 'unit' => 32];

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
        // Una unidad que no es texto (número o arreglo) cuenta como ausente: regla de dominio, nunca TypeError ni 500.
        $unit = is_string($d['unit'] ?? null) && trim($d['unit']) !== '' ? trim($d['unit']) : null;
        foreach (self::MAX_LENGTH as $field => $max) {
            if (mb_strlen(trim((string) ($field === 'unit' ? $unit : $d[$field]))) > $max) {
                throw new DomainRuleViolation('TEXT_TOO_LONG', $field);
            }
        }
        $num = static fn (string $k): ?float => is_numeric($d[$k] ?? null) && is_finite((float) $d[$k]) ? (float) $d[$k] : null;
        $test = new self($id, strtoupper(trim($d['code'])), trim($d['name']), $d['specimen_type'], $num('min_volume_ml') ?? 0.0,
            $d['result_type'], $unit, $num('reference_low'), $num('reference_high'), $num('critical_low'),
            $num('critical_high'), $versionNo, $d['valid_from']);
        $test->assertRules($today);

        return $test;
    }

    private function assertRules(string $today): void
    {
        $ranges = [$this->referenceLow, $this->referenceHigh, $this->criticalLow, $this->criticalHigh];
        $rule = match (true) {
            !preg_match('/^[A-Z0-9][A-Z0-9._-]{1,31}$/', $this->code) => ['CODE_INVALID', 'code'],
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
