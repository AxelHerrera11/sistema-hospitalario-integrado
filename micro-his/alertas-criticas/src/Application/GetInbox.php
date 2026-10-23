<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Application;

use MicroHis\Alertas\Domain\CriticalAlert;

/** UC-ALR-06 Bandeja: alertas pendientes de acuse del usuario, la más próxima a vencer primero. */
final class GetInbox
{
    public function __construct(private readonly AlertRepository $alerts) {}

    /** @return list<CriticalAlert> */
    public function execute(string $user): array
    {
        return $this->alerts->pendingFor($user);
    }
}
