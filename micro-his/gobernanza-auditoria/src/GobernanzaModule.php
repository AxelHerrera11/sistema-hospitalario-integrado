<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza;

use MicroHis\Gobernanza\Application\AccessGate;
use MicroHis\Gobernanza\Application\AuditFilterParser;
use MicroHis\Gobernanza\Application\AuditPermissions;
use MicroHis\Gobernanza\Application\Clock;
use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Application\SearchAuditEvents;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Presentation\AuditEventPresenter;
use MicroHis\Gobernanza\Presentation\AuditHttpHandler;

/**
 * Raíz de composición del módulo (refactorización de la Semana 7).
 *
 * Antes, la cadena RecordAuditEvent -> SearchAuditEvents -> AuditHttpHandler se
 * armaba a mano en tres lugares (proveedor Laravel, fixtures de prueba y prueba
 * de integración RBAC). Ahora hay un único punto: los adaptadores se inyectan
 * y el cableado interno no se repite.
 */
final readonly class GobernanzaModule
{
    private function __construct(
        public RecordAuditEvent $recorder,
        public SearchAuditEvents $search,
        public AuditHttpHandler $http,
    ) {
    }

    public static function compose(
        AuditLogger $logger,
        AuditEventReader $reader,
        AccessGate $gate,
        Clock $clock,
        FailureReporter $reporter,
        string $timezone = 'UTC',
        string $permission = AuditPermissions::VIEW,
    ): self {
        $recorder = new RecordAuditEvent($logger, $clock, $reporter);
        $search = new SearchAuditEvents($reader, $gate, $recorder, new AuditFilterParser($timezone), $permission);

        return new self($recorder, $search, new AuditHttpHandler($search, new AuditEventPresenter(), $reporter));
    }
}
