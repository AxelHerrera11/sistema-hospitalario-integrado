<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

/** Misma forma que el contrato allowed/reason/trace_id de RBAC. */
final readonly class AccessDecision
{
    /** Motivos de RBAC que indican dependencia caída (503), no falta de permiso (403). */
    private const DEPENDENCY_REASONS = ['resolucion_no_disponible', 'registro_no_disponible'];

    public function __construct(
        public bool $allowed,
        public ?string $reason,
        public ?string $traceId,
    ) {
    }

    public function dependencyUnavailable(): bool
    {
        return !$this->allowed && in_array($this->reason, self::DEPENDENCY_REASONS, true);
    }
}
