<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Integration;

use MicroHis\ExpedienteMedico\Application\AuditUnavailable;
use MicroHis\ExpedienteMedico\Application\ClinicalAccessAudit;
use MicroHis\ExpedienteMedico\Application\RequestContext;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Domain\InvalidAuditEvent;

/**
 * Adaptador a la bitácora real de Gobernanza y Auditoría (RecordAuditEvent → AuditLogger, append-only).
 * Todo acceso al expediente es criticidad Security: si no se persiste, RecordAuditEvent lanza y aquí se traduce
 * a AuditUnavailable. El evento se guarda como «expediente_medico.<accion>», con resource_type medical_record.
 */
final class GobernanzaClinicalAudit implements ClinicalAccessAudit
{
    public const MODULE = 'expediente_medico';

    public function __construct(private readonly RecordAuditEvent $recorder)
    {
    }

    public function record(RequestContext $request, string $action, string $outcome, ?string $recordId = null, ?string $reason = null, array $context = []): void
    {
        try {
            $this->recorder->record(
                $request->actorId,
                $request->hospitalId,
                self::MODULE,
                $action,
                $outcome === 'success' ? AuditOutcome::Success : AuditOutcome::Denied,
                AuditCriticality::Security,
                $recordId === null ? null : 'medical_record',
                $recordId,
                $request->correlationId,
                $reason,
                $context,
            );
        } catch (AuditStorageFailure|InvalidAuditEvent) {
            throw new AuditUnavailable('audit_not_recorded');
        }
    }
}
