<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * Criterios de consulta ya validados. El tenant NO forma parte del filtro:
 * lo aporta siempre el servidor al llamar a AuditEventReader::search().
 *
 * El rango es semiabierto [from, until): from incluido, until excluido.
 */
final readonly class AuditFilter
{
    public const DEFAULT_PER_PAGE = 25;
    public const MAX_PER_PAGE = 100;
    public const MAX_PAGE = 10000;

    public function __construct(
        public ?string $actorId = null,
        public ?string $module = null,
        public ?string $action = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $until = null,
        public ?AuditOutcome $outcome = null,
        public ?string $correlationId = null,
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
        if ($page < 1 || $page > self::MAX_PAGE || $perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new \InvalidArgumentException('pagination');
        }
        if ($from !== null && $until !== null && $from >= $until) {
            throw new \InvalidArgumentException('range');
        }
        if ($outcome === AuditOutcome::Unknown) {
            throw new \InvalidArgumentException('outcome');
        }
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /** ¿El evento cumple todos los criterios? (usado por el adaptador InMemory) */
    public function matches(AuditEvent $event): bool
    {
        return ($this->actorId === null || $event->actorId === $this->actorId)
            && ($this->module === null || $event->module === $this->module)
            && ($this->action === null || $event->action === $this->action)
            && ($this->from === null || $event->occurredAt >= $this->from)
            && ($this->until === null || $event->occurredAt < $this->until)
            && ($this->outcome === null || $event->outcome === $this->outcome)
            && ($this->correlationId === null || $event->correlationId === $this->correlationId);
    }
}
