<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * Contrato estable de escritura de la bitácora (append-only).
 *
 * - Solo agrega eventos: no existe operación de modificación ni de borrado.
 * - Si el almacén falla, lanza AuditStorageFailure; nunca descarta el evento
 *   en silencio. La decisión de abortar o continuar la toma RecordAuditEvent
 *   según AuditCriticality.
 * - Si la conexión ya tiene una transacción abierta, la implementación
 *   persistente participa en ella para que un ROLLBACK del consumidor revierta
 *   también el evento.
 */
interface AuditLogger
{
    /** @throws AuditStorageFailure */
    public function append(AuditEvent $event): void;
}
