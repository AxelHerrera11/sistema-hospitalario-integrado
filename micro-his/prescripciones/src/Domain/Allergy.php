<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Alergia activa del paciente, tal como la expone el registro de alergias de HOSPITAL. */
final readonly class Allergy
{
    public function __construct(
        public ?string $allergenCode,
        public ?string $allergenName,
        public AllergyType $type,
        public Severity $severity,
    ) {}

    /**
     * SD-PRE-01: es grave la alergia que no es intolerancia y cuya severidad es severe o life_threatening.
     * Es la regla de bloqueo sin excepción de la Semana 1; pendiente de validación por jefatura médica.
     */
    public function isGrave(): bool
    {
        return $this->type !== AllergyType::Intolerance
            && in_array($this->severity, [Severity::Severe, Severity::LifeThreatening], true);
    }

    /** Identificador legible del alérgeno: el código si no queda vacío tras recortar; si no, el nombre. */
    public function label(): string
    {
        $code = trim((string) $this->allergenCode);
        if ($code !== '') {
            return $code;
        }

        return trim((string) $this->allergenName);
    }

    /** Normalización SD-PRE-02: recorta, colapsa espacios, pasa a minúsculas y quita acentos. */
    public static function normalize(string $value): string
    {
        $folded = strtr(mb_strtolower(trim($value)), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return (string) preg_replace('/\s+/u', ' ', $folded);
    }
}
