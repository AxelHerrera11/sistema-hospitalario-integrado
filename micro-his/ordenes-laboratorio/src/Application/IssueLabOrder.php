<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

use Closure;
use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Domain\LabOrder;
use MicroHis\OrdenesLab\Domain\LabOrderItem;

/**
 * UC-OL-01 Emitir orden desde el expediente.
 * Orquesta idempotencia, coherencia expediente–episodio–nota, vigencia del catálogo y guardado.
 */
final class IssueLabOrder
{
    /**
     * @param Closure(): string $newId
     * @param Closure(): string $newEventId
     * @param Closure(): string $now
     * @param Closure(): string $newCorrelationId
     */
    public function __construct(
        private readonly LabOrderRepository $repository,
        private readonly ExpedienteGateway $expediente,
        private readonly Closure $newId,
        private readonly Closure $newEventId,
        private readonly Closure $now,
        private readonly Closure $newCorrelationId,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function execute(array $input, string $actor, string $tenant): LabOrder
    {
        return $this->executeOutcome($input, $actor, $tenant)->order;
    }

    /** @param array<string, mixed> $input */
    public function executeOutcome(array $input, string $actor, string $tenant): IssueLabOrderOutcome
    {
        $key = is_string($input['idempotency_key'] ?? null) ? trim($input['idempotency_key']) : '';
        if ($key === '') {
            throw new DomainRuleViolation('IDEMPOTENCY_KEY_REQUIRED', 'idempotency_key');
        }
        if (($existing = $this->repository->findByIdempotency($tenant, $key)) !== null) {
            return new IssueLabOrderOutcome($existing, true);
        }

        $patientId = is_string($input['local_patient_id'] ?? null) ? trim($input['local_patient_id']) : '';
        $episodeId = is_string($input['care_episode_id'] ?? null) ? trim($input['care_episode_id']) : '';
        if ($patientId === '' || $episodeId === '') {
            throw new DomainRuleViolation('FIELD_REQUIRED', $patientId === '' ? 'local_patient_id' : 'care_episode_id');
        }

        $context = $this->expediente->episodeContext($tenant, $patientId, $episodeId);
        if (($context['patient_id'] ?? '') !== $patientId || ($context['episode_id'] ?? '') !== $episodeId
            || !($context['open'] ?? false)) {
            throw new DomainRuleViolation('CARE_EPISODE_NOT_OPEN', 'care_episode_id');
        }

        $noteId = is_string($input['clinical_note_id'] ?? null) ? trim($input['clinical_note_id']) : '';
        if ($noteId !== '' && !in_array($noteId, $context['clinical_note_ids'] ?? [], true)) {
            throw new DomainRuleViolation('CLINICAL_NOTE_MISMATCH', 'clinical_note_id');
        }

        $testIds = $this->testIds($input['items'] ?? null);
        $active = $this->expediente->activeTests($tenant, $testIds);
        $items = [];
        foreach ($testIds as $testId) {
            if (!isset($active[$testId])) {
                throw new DomainRuleViolation('LAB_TEST_NOT_ACTIVE', 'items');
            }
            $items[] = new LabOrderItem($testId, $active[$testId]['code'], $active[$testId]['name']);
        }

        $draft = $input;
        $draft['tenant_id'] = $tenant;
        $draft['id'] = ($this->newId)();
        $draft['idempotency_key'] = $key;
        $order = LabOrder::issue($draft, $items, $actor, ($this->now)(), ($this->newCorrelationId)(), ($this->newEventId)());

        try {
            $this->repository->create($tenant, $order);
        } catch (PersistenceFailure $failure) {
            if ($failure->duplicate && ($existing = $this->repository->findByIdempotency($tenant, $key)) !== null) {
                return new IssueLabOrderOutcome($existing, true);
            }
            throw $failure;
        }

        return new IssueLabOrderOutcome($order, false);
    }

    /** @return list<string> */
    private function testIds(mixed $items): array
    {
        if (!is_array($items) || $items === []) {
            throw new DomainRuleViolation('ORDER_ITEMS_INVALID', 'items');
        }
        $ids = [];
        foreach ($items as $item) {
            $id = is_array($item) && is_string($item['lab_test_id'] ?? null) ? trim($item['lab_test_id']) : '';
            if ($id === '') {
                throw new DomainRuleViolation('ORDER_ITEMS_INVALID', 'items');
            }
            $ids[] = $id;
        }
        if (count($ids) !== count(array_unique($ids))) {
            throw new DomainRuleViolation('ORDER_ITEMS_INVALID', 'items');
        }

        return $ids;
    }
}
