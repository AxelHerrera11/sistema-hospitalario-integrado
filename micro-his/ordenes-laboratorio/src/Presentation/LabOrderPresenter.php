<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Presentation;

use MicroHis\OrdenesLab\Application\PersistenceFailure;
use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Domain\LabOrder;

/** Presentation (semana 3): formatea la respuesta y traduce errores a códigos estables. */
final class LabOrderPresenter
{
    /** @return array{status: int, body: array<string, mixed>} */
    public function created(LabOrder $order): array
    {
        return $this->withCorrelation(201, ['lab_order' => $order->toArray()], $order->correlationId);
    }

    /** @return array{status: int, body: array<string, mixed>} */
    public function ok(LabOrder $order): array
    {
        return $this->withCorrelation(200, ['lab_order' => $order->toArray()], $order->correlationId);
    }

    /**
     * @param array{items: list<LabOrder>, per_page: int, page: int} $result
     * @return array{status: int, body: array<string, mixed>}
     */
    public function list(array $result): array
    {
        return ['status' => 200, 'body' => [
            'lab_orders' => array_map(static fn (LabOrder $order): array => $order->toArray(), $result['items']),
            'meta' => ['per_page' => $result['per_page'], 'page' => $result['page']],
        ]];
    }

    /** @return array{status: int, body: array<string, mixed>} */
    public function error(\Throwable $error): array
    {
        if ($error instanceof PersistenceFailure) {
            return ['status' => 503, 'body' => ['error' => 'PERSISTENCE_FAILURE']];
        }
        if (!$error instanceof DomainRuleViolation) {
            return ['status' => 500, 'body' => ['error' => 'INTERNAL_ERROR']];
        }
        $status = match ($error->error) {
            'ORDER_NOT_FOUND' => 404,
            'INVALID_STATUS_TRANSITION', 'ORDER_ALREADY_VALIDATED' => 409,
            default => 422,
        };

        return ['status' => $status, 'body' => array_filter(['error' => $error->error, 'field' => $error->field], static fn (mixed $v): bool => $v !== '')];
    }

    /** @param array<string, mixed> $body @return array{status: int, body: array<string, mixed>} */
    private function withCorrelation(int $status, array $body, string $correlationId): array
    {
        $body['meta'] = ['correlation_id' => $correlationId];

        return ['status' => $status, 'body' => $body];
    }
}
