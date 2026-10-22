<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * Resultado de la operación auditada.
 *
 * Solo Success indica que la acción ocurrió; Failure y Denied son intentos que
 * no produjeron el cambio. Unknown existe únicamente para leer filas escritas
 * por otros productores sin columna result: nunca se usa al registrar.
 */
enum AuditOutcome: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Denied = 'denied';
    case Unknown = 'unknown';

    public function occurred(): bool
    {
        return $this === self::Success;
    }

    /** @return list<string> resultados aceptados al registrar y al filtrar */
    public static function recordable(): array
    {
        return [self::Success->value, self::Failure->value, self::Denied->value];
    }
}
