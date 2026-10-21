<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/** Página de resultados ordenada de más reciente a más antiguo. */
final readonly class AuditPage
{
    /** @param list<AuditEvent> $events */
    public function __construct(
        public array $events,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        return $this->total === 0 ? 0 : intdiv($this->total + $this->perPage - 1, $this->perPage);
    }
}
