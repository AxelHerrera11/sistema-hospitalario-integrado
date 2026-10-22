<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Domain;

final readonly class Medication
{
    private const CODE_PATTERN = '/^[A-Z0-9][A-Z0-9-]{1,19}$/';

    private function __construct(
        public ?string $id,
        public string $code,
        public string $name,
        public string $activeIngredient,
        public string $pharmacologicalGroup,
        public Validity $validity,
    ) {
    }

    /** Alta: valida y normaliza los datos; todo registro nuevo nace active. */
    public static function register(array $draft): self
    {
        $code = strtoupper(self::text($draft, 'code', 2, 20));
        if (!preg_match(self::CODE_PATTERN, $code)) {
            throw new MedicationRuleViolation('code', 'El código admite letras, dígitos y guiones (2 a 20).');
        }

        return new self(
            null,
            $code,
            self::text($draft, 'name', 2, 120),
            self::text($draft, 'active_ingredient', 2, 120),
            self::text($draft, 'pharmacological_group', 2, 120),
            Validity::Active,
        );
    }

    /** Reconstruye un registro ya confirmado por la persistencia. */
    public static function restore(string $id, string $code, string $name, string $activeIngredient, string $group, Validity $validity): self
    {
        return new self($id, $code, $name, $activeIngredient, $group, $validity);
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /** Cambia solo la vigencia; los demás datos y el registro se conservan. */
    public function changeValidity(Validity $validity): self
    {
        if ($validity === $this->validity) {
            throw new MedicationRuleViolation('validity', "El medicamento ya tiene vigencia {$validity->value}.");
        }

        return new self($this->id, $this->code, $this->name, $this->activeIngredient, $this->pharmacologicalGroup, $validity);
    }

    private static function text(array $draft, string $field, int $min, int $max): string
    {
        $value = trim((string) ($draft[$field] ?? ''));
        $length = mb_strlen($value);
        if ($length < $min || $length > $max) {
            throw new MedicationRuleViolation($field, "El campo {$field} es obligatorio y admite de {$min} a {$max} caracteres.");
        }

        return $value;
    }
}
