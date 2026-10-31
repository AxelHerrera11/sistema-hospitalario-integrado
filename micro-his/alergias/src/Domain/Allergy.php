<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Domain;

/** Alergia clínica de un paciente (UC-ALG-01). Nace válida o no nace. */
final readonly class Allergy
{
    public const TYPES = ['allergy', 'intolerance', 'unknown'];
    public const SEVERITIES = ['mild', 'moderate', 'severe', 'life_threatening', 'unknown'];
    /** Los mismos límites que la pantalla (maxlength): la API no confía en el cliente. */
    public const MAX_LENGTH = ['allergen_code' => 50, 'allergen_name' => 200, 'reaction' => 500];

    private function __construct(
        public string $id, public string $patientId, public ?string $allergenCode, public ?string $allergenName,
        public ?string $reaction, public string $type, public string $severity,
    ) {}

    /** @param array<string, mixed> $d */
    public static function register(string $id, string $patientId, array $d): self
    {
        $text = static fn (string $k): ?string => is_string($d[$k] ?? null) && trim($d[$k]) !== '' ? trim($d[$k]) : null;
        $tooLong = array_key_first(array_filter(self::MAX_LENGTH, static fn (int $max, string $k): bool => mb_strlen($text($k) ?? '') > $max, ARRAY_FILTER_USE_BOTH));
        $rule = match (true) {
            $text('allergen_code') === null && $text('allergen_name') === null => ['ALLERGEN_REQUIRED', 'allergen_name'],
            $tooLong !== null => ['TEXT_TOO_LONG', $tooLong],
            !in_array($d['allergy_type'] ?? null, self::TYPES, true) => ['ALLERGY_TYPE_INVALID', 'allergy_type'],
            !in_array($d['severity'] ?? null, self::SEVERITIES, true) => ['SEVERITY_INVALID', 'severity'],
            default => null,
        };
        if ($rule !== null) {
            throw new DomainRuleViolation(...$rule);
        }

        return new self($id, $patientId, $text('allergen_code'), $text('allergen_name'), $text('reaction'), $d['allergy_type'], $d['severity']);
    }
}
