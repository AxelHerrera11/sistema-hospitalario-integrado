<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Domain\InvalidAuditEvent;

/**
 * Caso de uso: registrar un evento de auditoría aplicando la política de fallo.
 *
 * - El identificador y la marca temporal los genera el servidor.
 * - InvalidAuditEvent se reporta y se propaga siempre (es un error del consumidor).
 * - AuditStorageFailure:
 *     Security      -> se reporta y se relanza; el consumidor debe abortar.
 *     Informational -> se reporta y se devuelve false; la operación sigue.
 */
final class RecordAuditEvent
{
    public function __construct(
        private readonly AuditLogger $logger,
        private readonly Clock $clock,
        private readonly FailureReporter $reporter,
    ) {
    }

    /**
     * @param array<mixed> $context
     * @return bool true si el evento quedó persistido
     * @throws InvalidAuditEvent
     * @throws AuditStorageFailure solo para eventos Security
     */
    public function record(
        string $actorId,
        string $tenantId,
        string $module,
        string $action,
        AuditOutcome $outcome,
        AuditCriticality $criticality,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?string $correlationId = null,
        ?string $reason = null,
        array $context = [],
    ): bool {
        try {
            $event = AuditEvent::record(
                self::uuid4(),
                $this->clock->now(),
                $actorId,
                $tenantId,
                $module,
                $action,
                $outcome,
                $criticality,
                $resourceType,
                $resourceId,
                $correlationId,
                $reason,
                $context,
            );
        } catch (InvalidAuditEvent $invalid) {
            // Hallazgo H-GOB-04: sin este reporte, un evento rechazado (p. ej. una
            // denegación RBAC sin user_id, que Authorizer silencia) no dejaba rastro.
            $this->reporter->report('audit_event_invalid', [
                'field' => $invalid->getMessage(),
                'criticality' => $criticality->value,
                'correlation_id' => is_string($correlationId) && strlen($correlationId) <= 64 ? $correlationId : null,
            ]);
            throw $invalid;
        }

        try {
            $this->logger->append($event);
        } catch (AuditStorageFailure $failure) {
            $this->reporter->report('audit_record_failed', [
                'event' => $event->eventName(),
                'criticality' => $criticality->value,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'cause' => $failure->getMessage(),
            ]);
            if ($criticality->mandatory()) {
                throw $failure;
            }

            return false;
        }

        return true;
    }

    private static function uuid4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
