<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/**
 * Bitácora de accesos al expediente. Adaptador real: Integration\GobernanzaClinicalAudit sobre RecordAuditEvent
 * (Gobernanza y Auditoría). Solo metadatos: identificadores y conteos, nunca nombres ni motivos clínicos.
 */
interface ClinicalAccessAudit
{
    public const OPENED = 'abierto';
    public const REPEATED_OPENING = 'apertura_repetida';
    public const CONSULTED = 'consultado';
    public const DENIED = 'acceso_denegado';

    /**
     * @param 'success'|'denied' $outcome
     * @param array<string, int|string> $context
     * @throws AuditUnavailable si el evento no quedó persistido
     */
    public function record(RequestContext $request, string $action, string $outcome, ?string $recordId = null, ?string $reason = null, array $context = []): void;
}
