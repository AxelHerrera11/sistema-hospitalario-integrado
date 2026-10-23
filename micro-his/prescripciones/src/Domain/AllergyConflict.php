<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Conflicto entre un alérgeno del paciente y un dato del medicamento (principio activo o grupo). */
final readonly class AllergyConflict
{
    public const ACTIVE_INGREDIENT = 'active_ingredient';

    public const PHARMACOLOGICAL_GROUP = 'pharmacological_group';

    public function __construct(
        public string $allergen,
        public AllergyType $type,
        public Severity $severity,
        public string $matchedOn,
    ) {}

    /** SD-PRE-01: sigue la misma regla que Allergy::isGrave(). */
    public function isGrave(): bool
    {
        return $this->type !== AllergyType::Intolerance
            && in_array($this->severity, [Severity::Severe, Severity::LifeThreatening], true);
    }

    /** Clave de cobertura: alérgeno normalizado y dato del medicamento que coincidió (SD-PRE-02). */
    public function key(): string
    {
        return Allergy::normalize($this->allergen).'|'.$this->matchedOn;
    }

    /** @return array{allergen: string, allergy_type: string, severity: string, matched_on: string} */
    public function toArray(): array
    {
        return [
            'allergen' => $this->allergen,
            'allergy_type' => $this->type->value,
            'severity' => $this->severity->value,
            'matched_on' => $this->matchedOn,
        ];
    }
}
