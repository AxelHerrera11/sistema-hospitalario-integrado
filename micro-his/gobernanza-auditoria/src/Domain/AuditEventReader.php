<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * Contrato de lectura de la bitácora. El tenant es obligatorio y siempre lo
 * aporta el servidor: la implementación debe restringir cada consulta a él.
 */
interface AuditEventReader
{
    /** @throws AuditStorageFailure */
    public function search(string $tenantId, AuditFilter $filter): AuditPage;
}
