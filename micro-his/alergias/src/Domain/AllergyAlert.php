<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Domain;

/** Alerta visual del expediente (UC-ALG-05). Falla segura: sin datos es «no disponible», nunca «sin alergias». */
final readonly class AllergyAlert
{
    /** @param list<array<string, mixed>> $allergies */
    private function __construct(public string $level, public string $message, public array $allergies) {}

    /** @param list<array<string, mixed>> $active */
    public static function from(array $active): self
    {
        $critical = array_filter($active, static fn (array $a): bool => in_array($a['severity'], ['severe', 'life_threatening'], true));

        return match (true) {
            $active === [] => new self('none', 'Sin alergias registradas', []),
            $critical !== [] => new self('critical', 'ALERGIA GRAVE: verificar antes de prescribir', $active),
            default => new self('warning', 'Alergias registradas', $active),
        };
    }

    public static function unavailable(): self
    {
        return new self('unavailable', 'Alergias no disponibles: verificar con el paciente antes de prescribir', []);
    }
}
