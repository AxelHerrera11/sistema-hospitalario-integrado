<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Presentation;

use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\GetInbox;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Application\PersistenceFailure;
use MicroHis\Alertas\Domain\DomainRuleViolation;

/** Controller (MVC): traduce HTTP a casos de uso, elige JSON o la View de la bandeja y mapea errores. Sin reglas de negocio. */
final class CriticalAlertController
{
    private const STATUS = ['ALERT_NOT_FOUND' => 404, 'NO_CARE_RELATION' => 403, 'ALERT_ALREADY_ACKNOWLEDGED' => 409];

    public function __construct(
        private readonly HandleResultValidated $handle, private readonly AcknowledgeAlert $acknowledge,
        private readonly GetInbox $inbox, private readonly AlertInboxView $view,
    ) {}

    /** @param array<string, mixed> $input @return array{0: int, 1: array<string, mixed>|string} */
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
            if ("{$method} {$path}" === 'GET /critical-alerts') {
                $alerts = $this->inbox->execute($user);
                return [200, ($input['format'] ?? '') === 'html' ? $this->view->render($alerts) : ['alerts' => array_map('get_object_vars', $alerts)]];
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
