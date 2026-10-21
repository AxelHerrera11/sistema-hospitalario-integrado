<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditPage;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;

/**
 * Caso de uso: consulta autorizada de la bitácora del tenant actual.
 *
 * Orden: 1) autorizar (RBAC) antes de mirar los filtros, para no revelar
 * nada a quien no tiene permiso; 2) validar filtros; 3) leer restringido al
 * tenant del servidor; 4) registrar la propia consulta como evento Security
 * (gobernanza.auditoria_consultada). Si ese registro falla, no se entregan
 * resultados (falla cerrada). Las denegaciones las registra RBAC a través de
 * su DenialRecorder; aquí no se duplican.
 */
final class SearchAuditEvents
{
    public function __construct(
        private readonly AuditEventReader $reader,
        private readonly AccessGate $gate,
        private readonly RecordAuditEvent $recorder,
        private readonly AuditFilterParser $parser,
        private readonly string $permission = AuditPermissions::VIEW,
    ) {
    }

    /**
     * @param array<mixed> $input parámetros públicos de consulta (sin tenant)
     * @throws AccessDenied
     * @throws InvalidAuditFilter
     * @throws AuditStorageFailure
     */
    public function execute(AccessContext $context, array $input): AuditPage
    {
        $decision = $this->gate->decide($context->userId, $context->tenantId, $this->permission);
        if (!$decision->allowed) {
            throw new AccessDenied($decision);
        }

        $filter = $this->parser->parse($input);
        $page = $this->reader->search($context->tenantId, $filter);

        $this->recorder->record(
            $context->userId,
            $context->tenantId,
            'gobernanza',
            'auditoria_consultada',
            AuditOutcome::Success,
            AuditCriticality::Security,
            correlationId: $context->correlationId,
            context: self::describe($filter) + ['returned' => count($page->events), 'total' => $page->total],
        );

        return $page;
    }

    /** @return array<string, scalar|null> filtros usados, sin datos sensibles */
    private static function describe(AuditFilter $filter): array
    {
        return array_filter([
            'filter_actor' => $filter->actorId,
            'filter_module' => $filter->module,
            'filter_action' => $filter->action,
            'filter_from' => $filter->from?->format(DATE_ATOM),
            'filter_until' => $filter->until?->format(DATE_ATOM),
            'filter_outcome' => $filter->outcome?->value,
            'filter_correlation' => $filter->correlationId,
            'page' => $filter->page,
            'per_page' => $filter->perPage,
        ], static fn ($v): bool => $v !== null);
    }
}
