<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Domain\LabOrder;

/** UC-OL-05 Listar órdenes del paciente o episodio con paginación acotada. */
final class ListLabOrders
{
    public function __construct(private readonly LabOrderRepository $repository)
    {
    }

    /**
     * @param array{patient_id?: string, episode_id?: string, status?: string} $filters
     * @return array{items: list<LabOrder>, per_page: int, page: int}
     */
    public function execute(string $tenant, array $filters, int $perPage = 20, int $page = 1): array
    {
        if ($perPage < 1 || $perPage > 50) {
            throw new DomainRuleViolation('PAGINATION_INVALID', 'per_page');
        }
        if ($page < 1) {
            throw new DomainRuleViolation('PAGINATION_INVALID', 'page');
        }
        if (isset($filters['status']) && $filters['status'] !== ''
            && !in_array(strtoupper($filters['status']), LabOrder::STATUSES, true)) {
            throw new DomainRuleViolation('STATUS_UNSUPPORTED', 'status');
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $filters['status'] = strtoupper($filters['status']);
        }

        return [
            'items' => $this->repository->search($tenant, $filters, $perPage, ($page - 1) * $perPage),
            'per_page' => $perPage,
            'page' => $page,
        ];
    }
}
