<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

/**
 * Bitácora de auditoría local (entidad `local_access_audit`, slice H8; columnas del Anexo A de la Semana 1).
 * Solo metadatos: nunca texto clínico (RNF-SOAP-05). El repositorio la invoca dentro de su escritura,
 * así nota y auditoría se guardan juntas o ninguna.
 */
interface AuditTrail
{
    /**
     * @param string $action `modulo.accion`, por ejemplo `nota_soap.registrada`
     * @param array<string, scalar> $metadata identificadores y códigos, sin texto de las secciones
     * @throws \RuntimeException si la bitácora no está disponible
     */
    public function record(string $actorId, string $action, string $resourceType, string $resourceId, string $episodeId, array $metadata = []): void;
}
