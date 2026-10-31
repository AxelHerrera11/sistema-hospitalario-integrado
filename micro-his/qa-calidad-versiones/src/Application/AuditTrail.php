<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/**
 * Puerto hacia Gobernanza y Auditoría. Adaptador real: Integration\GobernanzaAuditTrail
 * (RecordAuditEvent). Si un evento de seguridad no se puede guardar lanza QaStorageFailure.
 */
interface AuditTrail
{
    /**
     * @param  'success'|'denied'|'failure'  $outcome
     * @param  ?string  $reason  código [a-z0-9_] (formato AuditEvent::REASON)
     * @param  array<string, scalar|null>  $context  sin secretos ni datos clínicos
     */
    public function record(Actor $actor, string $action, string $outcome, string $targetId, string $traceId, ?string $reason = null, array $context = []): void;
}
