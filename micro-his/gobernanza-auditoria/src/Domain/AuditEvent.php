<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * Evento de auditoría inmutable: quién (actor + tenant), qué (módulo.acción),
 * sobre qué (recurso), cuándo (occurredAt, reloj del servidor, UTC) y con qué
 * resultado. No expone setters ni operaciones de modificación.
 */
final readonly class AuditEvent
{
    public const MODULE = '/^[a-z][a-z0-9_]{0,39}$/D';
    public const ACTION = '/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*$/D';
    public const IDENTIFIER = '/^[A-Za-z0-9][A-Za-z0-9._:@-]{0,127}$/D';
    public const CORRELATION = '/^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$/D';
    public const REASON = '/^[a-z][a-z0-9_]{0,63}$/D';
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D';

    /**
     * @param array<string, scalar|null|list<scalar|null>> $context
     * @param list<string> $redactedKeys
     */
    private function __construct(
        public string $eventId,
        public \DateTimeImmutable $occurredAt,
        public string $actorId,
        public string $tenantId,
        public string $module,
        public string $action,
        public AuditOutcome $outcome,
        public ?AuditCriticality $criticality,
        public ?string $resourceType,
        public ?string $resourceId,
        public ?string $correlationId,
        public ?string $reason,
        public array $context,
        public array $redactedKeys,
    ) {
    }

    /**
     * Crea un evento nuevo con validación estricta. Ningún dato se completa
     * por defecto: si falta información obligatoria, se lanza InvalidAuditEvent.
     *
     * @param array<mixed> $context contexto propuesto; se filtra con ContextSanitizer
     */
    public static function record(
        string $eventId,
        \DateTimeImmutable $occurredAt,
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
    ): self {
        self::require(preg_match(self::UUID, $eventId) === 1, 'event_id');
        self::require(preg_match(self::IDENTIFIER, $actorId) === 1, 'actor_id');
        self::require(preg_match(self::IDENTIFIER, $tenantId) === 1, 'tenant_id');
        self::require(preg_match(self::MODULE, $module) === 1, 'module');
        self::require(preg_match(self::ACTION, $action) === 1 && strlen($action) <= 80, 'action');
        self::require($outcome !== AuditOutcome::Unknown, 'outcome');
        self::require($resourceType === null || preg_match(self::MODULE, $resourceType) === 1, 'resource_type');
        self::require($resourceId === null || preg_match(self::IDENTIFIER, $resourceId) === 1, 'resource_id');
        self::require($resourceId === null || $resourceType !== null, 'resource_type');
        self::require($correlationId === null || preg_match(self::CORRELATION, $correlationId) === 1, 'correlation_id');
        self::require($reason === null || preg_match(self::REASON, $reason) === 1, 'reason');
        [$clean, $removed] = ContextSanitizer::sanitize($context);

        return new self(
            $eventId,
            $occurredAt->setTimezone(new \DateTimeZone('UTC')),
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
            $clean,
            $removed,
        );
    }

    /**
     * Reconstruye un evento ya almacenado. Es tolerante con filas de otros
     * productores (sin resultado o sin criticidad), pero no inventa valores:
     * lo ausente queda como Unknown/null. El contexto se vuelve a filtrar.
     *
     * @param array<mixed> $context
     * @param list<string> $redactedKeys
     */
    public static function rehydrate(
        string $eventId,
        \DateTimeImmutable $occurredAt,
        string $actorId,
        string $tenantId,
        string $eventName,
        ?string $outcome,
        ?string $criticality,
        ?string $resourceType,
        ?string $resourceId,
        ?string $correlationId,
        ?string $reason,
        array $context,
        array $redactedKeys = [],
    ): self {
        self::require($eventId !== '' && $actorId !== '' && $tenantId !== '' && $eventName !== '', 'stored_row');
        $dot = strpos($eventName, '.');
        [$clean, $removed] = ContextSanitizer::sanitize($context);
        // H-GOB-09: los campos de filas ajenas que no cumplen el formato no se muestran; se anotan.
        foreach (['resource_type' => [&$resourceType, self::MODULE], 'resource_id' => [&$resourceId, self::IDENTIFIER], 'correlation_id' => [&$correlationId, self::CORRELATION]] as $field => [&$value, $pattern]) {
            if ($value !== null && preg_match($pattern, $value) !== 1) {
                $value = null;
                $removed[] = $field;
            }
        }

        return new self(
            $eventId,
            $occurredAt->setTimezone(new \DateTimeZone('UTC')),
            $actorId,
            $tenantId,
            $dot === false ? $eventName : substr($eventName, 0, $dot),
            $dot === false ? '' : substr($eventName, $dot + 1),
            AuditOutcome::tryFrom((string) $outcome) ?? AuditOutcome::Unknown,
            AuditCriticality::tryFrom((string) $criticality),
            $resourceType,
            $resourceId,
            $correlationId,
            $reason !== null && preg_match(self::REASON, $reason) === 1 ? $reason : null,
            $clean,
            array_values(array_unique(array_merge(
                array_values(array_filter($redactedKeys, 'is_string')),
                $removed,
            ))),
        );
    }

    /** Nombre canónico almacenado: "modulo.accion" (por ejemplo "rbac.access_denied"). */
    public function eventName(): string
    {
        return $this->action === '' ? $this->module : $this->module . '.' . $this->action;
    }

    private static function require(bool $condition, string $field): void
    {
        if (!$condition) {
            throw new InvalidAuditEvent($field);
        }
    }
}
