<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Integration;

use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Domain\InvalidAuditEvent;
use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Application\AuditTrail;
use MicroHis\QaCalidad\Domain\QaStorageFailure;

/**
 * Adaptador al contrato público de Gobernanza y Auditoría (RecordAuditEvent).
 * Denegaciones y decisiones de gate son Security: si no se guardan, la operación
 * se aborta. El resto es Informational y no detiene el flujo.
 */
final class GobernanzaAuditTrail implements AuditTrail
{
    public const MODULE = 'qa_calidad';

    private const SECURITY = ['qa.acceso.denegado', 'qa.gate.evaluado'];

    public function __construct(private readonly RecordAuditEvent $recorder) {}

    public function record(Actor $actor, string $action, string $outcome, string $targetId, string $traceId, ?string $reason = null, array $context = []): void
    {
        try {
            $this->recorder->record(
                $actor->userId,
                $actor->tenantId,
                self::MODULE,
                $action,
                AuditOutcome::from($outcome),
                in_array($action, self::SECURITY, true) ? AuditCriticality::Security : AuditCriticality::Informational,
                'qa_test_run',
                $targetId,
                $traceId,
                $reason,
                $context,
            );
        } catch (AuditStorageFailure|InvalidAuditEvent $e) {
            throw new QaStorageFailure('No se pudo registrar el evento de auditoría QA.', 0, $e);
        }
    }
}
