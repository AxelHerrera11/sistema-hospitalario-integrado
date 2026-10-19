<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Application;

use Closure;
use MicroHis\Alertas\Domain\CriticalAlert;
use MicroHis\Alertas\Domain\DomainRuleViolation;

/** Consume «resultado validado» (ASII-20): detecta y genera la alerta una sola vez (idempotente por resultado). */
final class HandleResultValidated
{
    /** @param Closure(): \DateTimeImmutable $now @param Closure(): string $newId */
    public function __construct(private readonly AlertStore $store, private readonly Closure $now, private readonly Closure $newId) {}

    /** @param array<string, mixed> $event @return array{outcome: string, alert: ?CriticalAlert} */
    public function execute(array $event, string $actor): array
    {
        foreach (['result_version_id', 'lab_test_id', 'patient_id', 'episode_id', 'value'] as $field) {
            if ($field === 'value' ? !is_numeric($event[$field] ?? null) : !is_string($event[$field] ?? null)) {
                throw new DomainRuleViolation('EVENT_INVALID', $field);
            }
        }
        $alert = CriticalAlert::detect(($this->newId)(), $event, $this->store->thresholds($event['lab_test_id']),
            $this->store->attending($event['episode_id']), ($this->now)());
        if ($alert === null) {
            return ['outcome' => 'not_critical', 'alert' => null];
        }
        try {
            $this->store->save($alert, 'alerta.generada', $actor);
        } catch (PersistenceFailure $failure) {
            return $failure->duplicate ? ['outcome' => 'duplicate', 'alert' => null] : throw $failure;
        }
        return ['outcome' => 'created', 'alert' => $alert];
    }
}
