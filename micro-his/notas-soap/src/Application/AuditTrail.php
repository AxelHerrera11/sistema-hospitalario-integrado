<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

/**
 * Bitácora de auditoría del módulo. Solo identificadores, código y acción: nunca texto clínico (RNF-SOAP-05).
 * El repositorio la invoca dentro de su escritura, así nota y auditoría se guardan juntas o ninguna.
 */
interface AuditTrail
{
    /** @throws \RuntimeException si la bitácora no está disponible */
    public function record(string $event, string $actorId, string $detail): void;
}
