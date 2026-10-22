<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Presentation;

use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Application\PersistenceFailure;
use MicroHis\Alertas\Domain\DomainRuleViolation;

/** Traduce HTTP a casos de uso y errores a códigos de estado. No contiene reglas de negocio. */
final class CriticalAlertHttpHandler
{
    private const STATUS = ['ALERT_NOT_FOUND' => 404, 'NO_CARE_RELATION' => 403, 'ALERT_ALREADY_ACKNOWLEDGED' => 409];

    public function __construct(private readonly HandleResultValidated $handle, private readonly AcknowledgeAlert $acknowledge) {}

    /** @param array<string, mixed> $input @return array{0: int, 1: array<string, mixed>} */
    public function handle(string $method, string $path, array $input, ?string $user): array
    {
        if ($user === null) {
            return [401, ['error' => 'UNAUTHENTICATED']];
        }
        try {
            if ("{$method} {$path}" === 'POST /internal/result-validated') {
                $result = $this->handle->execute($input, $user);
                return [$result['outcome'] === 'created' ? 201 : 200, ['outcome' => $result['outcome'], 'alert' => $result['alert'] ? get_object_vars($result['alert']) : null]];
            }
            if ($method === 'POST' && preg_match('#^/critical-alerts/([\w-]+)/acknowledge$#', $path, $m)) {
                return [200, ['alert' => get_object_vars($this->acknowledge->execute($m[1], $user, (array) ($input['read_back'] ?? [])))]];
            }
            return [404, ['error' => 'NOT_FOUND']];
        } catch (DomainRuleViolation $v) {
            return [self::STATUS[$v->rule] ?? 422, ['error' => $v->rule, 'field' => $v->field]];
        } catch (PersistenceFailure) {
            return [500, ['error' => 'PERSISTENCE_FAILURE']];
        }
    }
}
