<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Persistence;

use MicroHis\OrdenesLab\Application\LabOrderRepository;
use MicroHis\OrdenesLab\Application\PersistenceFailure;
use MicroHis\OrdenesLab\Domain\LabOrder;
use MicroHis\OrdenesLab\Domain\LabOrderEvent;
use MicroHis\OrdenesLab\Domain\LabOrderItem;
use PDO;
use PDOException;

/** Adaptador PDO portable (PostgreSQL y SQLite) con sentencias preparadas y transacciones. */
final class PdoLabOrderRepository implements LabOrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function find(string $tenant, string $id): ?LabOrder
    {
        $row = $this->query(
            'SELECT * FROM lab_orders WHERE tenant_id = ? AND lab_order_id = ?',
            [$tenant, $id],
        )->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByIdempotency(string $tenant, string $key): ?LabOrder
    {
        $id = $this->query(
            'SELECT lab_order_id FROM lab_orders WHERE tenant_id = ? AND idempotency_key = ?',
            [$tenant, $key],
        )->fetchColumn();

        return $id === false ? null : $this->find($tenant, (string) $id);
    }

    public function create(string $tenant, LabOrder $order): void
    {
        $this->transactional(function () use ($tenant, $order): void {
            $this->query(
                'INSERT INTO lab_orders (lab_order_id, tenant_id, local_patient_id, care_episode_id, clinical_note_id,
                    ordered_by, priority, clinical_context, status, idempotency_key, correlation_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$order->id, $tenant, $order->patientId, $order->careEpisodeId, $order->clinicalNoteId,
                    $order->orderedBy, $order->priority, $order->clinicalContext, $order->status,
                    $order->idempotencyKey, $order->correlationId, $order->createdAt, $order->updatedAt],
            );
            foreach ($order->items as $item) {
                $this->query(
                    'INSERT INTO lab_order_items (lab_order_id, lab_test_id, test_code, test_name) VALUES (?, ?, ?, ?)',
                    [$order->id, $item->labTestId, $item->code, $item->name],
                );
            }
            foreach ($order->events as $index => $event) {
                $this->insertEvent($order->id, $index + 1, $event);
            }
        });
    }

    public function updateStatus(string $tenant, LabOrder $order, LabOrderEvent $event): void
    {
        $this->transactional(function () use ($tenant, $order, $event): void {
            $this->query(
                'UPDATE lab_orders SET status = ?, updated_at = ? WHERE tenant_id = ? AND lab_order_id = ?',
                [$order->status, $order->updatedAt, $tenant, $order->id],
            );
            $sequence = count($order->events);
            $this->insertEvent($order->id, $sequence, $event);
        });
    }

    public function search(string $tenant, array $filters, int $limit, int $offset): array
    {
        $where = ['tenant_id = ?'];
        $params = [$tenant];
        foreach (['patient_id' => 'local_patient_id', 'episode_id' => 'care_episode_id', 'status' => 'status'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $where[] = "{$column} = ?";
                $params[] = $filters[$key];
            }
        }
        $params[] = $limit;
        $params[] = $offset;
        $rows = $this->query(
            'SELECT * FROM lab_orders WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT ? OFFSET ?',
            $params,
            [count($params) - 2 => PDO::PARAM_INT, count($params) - 1 => PDO::PARAM_INT],
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $row): LabOrder => $this->hydrate($row), $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): LabOrder
    {
        $orderId = (string) $row['lab_order_id'];
        $items = array_map(
            static fn (array $item): LabOrderItem => new LabOrderItem(
                (string) $item['lab_test_id'],
                (string) $item['test_code'],
                (string) $item['test_name'],
            ),
            $this->query('SELECT * FROM lab_order_items WHERE lab_order_id = ? ORDER BY lab_test_id', [$orderId])->fetchAll(PDO::FETCH_ASSOC),
        );
        $events = array_map(
            static fn (array $event): LabOrderEvent => new LabOrderEvent(
                (string) $event['event_id'],
                $event['from_status'] === null ? null : (string) $event['from_status'],
                (string) $event['to_status'],
                (string) $event['actor'],
                (string) $event['occurred_at'],
                (string) $event['correlation_id'],
                $event['reason'] === null ? null : (string) $event['reason'],
            ),
            $this->query('SELECT * FROM lab_order_events WHERE lab_order_id = ? ORDER BY event_seq', [$orderId])->fetchAll(PDO::FETCH_ASSOC),
        );

        return LabOrder::restore(
            $orderId,
            (string) $row['tenant_id'],
            (string) $row['local_patient_id'],
            (string) $row['care_episode_id'],
            $row['clinical_note_id'] === null ? null : (string) $row['clinical_note_id'],
            (string) $row['ordered_by'],
            (string) $row['priority'],
            $row['clinical_context'] === null ? null : (string) $row['clinical_context'],
            (string) $row['idempotency_key'],
            (string) $row['correlation_id'],
            (string) $row['status'],
            $items,
            $events,
            (string) $row['created_at'],
            (string) $row['updated_at'],
        );
    }

    private function insertEvent(string $orderId, int $sequence, LabOrderEvent $event): void
    {
        $this->query(
            'INSERT INTO lab_order_events (event_id, lab_order_id, event_seq, from_status, to_status, actor,
                occurred_at, correlation_id, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$event->id, $orderId, $sequence, $event->fromStatus, $event->toStatus, $event->actor,
                $event->occurredAt, $event->correlationId, $event->reason],
        );
    }

    /** @param callable(): void $work */
    private function transactional(callable $work): void
    {
        try {
            $this->pdo->beginTransaction();
            $work();
            $this->pdo->commit();
        } catch (PDOException $error) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new PersistenceFailure(str_starts_with((string) $error->getCode(), '23'), $error);
        }
    }

    /** @param list<mixed> $params @param array<int, int> $types */
    private function query(string $sql, array $params, array $types = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $statement->bindValue($index + 1, $value, $types[$index] ?? (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
        }
        $statement->execute();

        return $statement;
    }
}
