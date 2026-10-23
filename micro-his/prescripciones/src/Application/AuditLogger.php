<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

/**
 * Puerto de auditoría (externo al módulo, provisto por HIS central).
 * Implementación real: App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface::logRaw()
 */
interface AuditLogger
{
    /**
     * Registra un evento de auditoría.
     *
     * @throws AuditUnavailable si el log de auditoría no está disponible
     */
    public function record(string $eventName, array $payload): void;
}
