<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Application;

use MicroHis\Alertas\Domain\CriticalAlert;
use MicroHis\Alertas\Domain\DomainRuleViolation;

/** UC-ALR-04 Acusar recibo: detiene el escalamiento. El primero que acusa gana. */
final class AcknowledgeAlert
{
    public function __construct(private readonly AlertStore $store) {}

    /** @param array<string, mixed> $readBack */
    public function execute(string $alertId, string $user, array $readBack): CriticalAlert
    {
        $alert = $this->store->find($alertId) ?? throw new DomainRuleViolation('ALERT_NOT_FOUND', 'alert_id');
        $alert->acknowledge($user, $readBack, $this->store->caresFor($user, $alert->patientId));
        try {
            $this->store->save($alert, 'alerta.acusada', $user);
        } catch (PersistenceFailure $failure) {
            throw $failure->duplicate ? new DomainRuleViolation('ALERT_ALREADY_ACKNOWLEDGED', 'status') : $failure;
        }
        return $alert;
    }
}
