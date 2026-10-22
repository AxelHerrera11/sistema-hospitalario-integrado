<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Presentation;

use MicroHis\Gobernanza\Application\AccessContext;
use MicroHis\Gobernanza\Application\AccessDenied;
use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Application\InvalidAuditFilter;
use MicroHis\Gobernanza\Application\SearchAuditEvents;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;

/**
 * Controlador HTTP delgado de GET /api/v1/audit-events.
 *
 * $server trae la identidad resuelta por el servidor (user_id del JWT,
 * tenant_id del middleware de tenant); nunca se toma de la petición.
 * Errores con el mismo formato que RBAC: {"code": ..., "trace_id": ...}.
 */
final class AuditHttpHandler
{
    private const TENANT_PARAMS = ['tenant_id', 'hospital_id', 'tenant', 'hospital'];

    public function __construct(
        private readonly SearchAuditEvents $search,
        private readonly AuditEventPresenter $presenter,
        private readonly FailureReporter $reporter,
    ) {
    }

    /**
     * @param array{user_id?: mixed, tenant_id?: mixed} $server
     * @param array<string, mixed> $headers cabeceras (nombres sin distinguir mayúsculas)
     * @param array<mixed> $query
     * @return array{status: int, body: array<string, mixed>, headers: array<string, string>}
     */
    public function search(array $server, array $headers, array $query): array
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        $trace = self::correlation($headers['x-request-id'] ?? null);
        $userId = $server['user_id'] ?? null;
        $tenantId = $server['tenant_id'] ?? null;

        if (!is_string($userId) || $userId === '') {
            return self::error(401, 'AUTH_REQUIRED', $trace);
        }
        if (!is_string($tenantId) || $tenantId === '') {
            return self::error(503, 'TENANT_CONTEXT_UNAVAILABLE', $trace);
        }
        $header = $headers['x-tenant-id'] ?? null;
        if (!is_string($header) || $header === '') {
            return self::error(400, 'TENANT_REQUIRED', $trace);
        }
        if (!hash_equals($tenantId, $header)) {
            return self::error(403, 'TENANT_MISMATCH', $trace);
        }
        foreach (self::TENANT_PARAMS as $param) {
            if (array_key_exists($param, $query)) {
                // El tenant lo fija el servidor: solo se tolera si coincide.
                if (!is_string($query[$param]) || !hash_equals($tenantId, $query[$param])) {
                    return self::error(403, 'TENANT_MISMATCH', $trace);
                }
                unset($query[$param]);
            }
        }

        try {
            $page = $this->search->execute(new AccessContext($userId, $tenantId, $trace), $query);

            return self::json(200, $this->presenter->page($page) + ['trace_id' => $trace], $trace);
        } catch (AccessDenied $denied) {
            $decision = $denied->decision;
            $dependency = $decision->dependencyUnavailable();

            return self::error($dependency ? 503 : 403, $dependency ? 'RBAC_UNAVAILABLE' : 'RBAC_DENIED', $decision->traceId ?? $trace);
        } catch (InvalidAuditFilter $invalid) {
            return self::json(422, ['code' => 'AUDIT_FILTER_INVALID', 'errors' => $invalid->errors, 'trace_id' => $trace], $trace);
        } catch (AuditStorageFailure $failure) {
            $this->reporter->report('audit_query_unavailable', ['cause' => $failure->getMessage(), 'tenant_id' => $tenantId, 'correlation_id' => $trace]);

            return self::error(503, 'AUDIT_UNAVAILABLE', $trace);
        } catch (\Throwable $error) {
            $this->reporter->report('audit_query_internal_error', ['type' => $error::class, 'correlation_id' => $trace]);

            return self::error(500, 'INTERNAL_ERROR', $trace);
        }
    }

    /** Reutiliza X-Request-ID si tiene formato seguro; si no, genera uno. */
    private static function correlation(mixed $value): string
    {
        return is_string($value) && preg_match(AuditEvent::CORRELATION, $value) === 1
            ? $value
            : 'req-' . bin2hex(random_bytes(8));
    }

    /** @return array{status: int, body: array<string, mixed>, headers: array<string, string>} */
    private static function error(int $status, string $code, string $trace): array
    {
        return self::json($status, ['code' => $code, 'trace_id' => $trace], $trace);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{status: int, body: array<string, mixed>, headers: array<string, string>}
     */
    private static function json(int $status, array $body, string $trace): array
    {
        return [
            'status' => $status,
            'body' => $body,
            'headers' => ['Cache-Control' => 'no-store, private', 'X-Request-ID' => $trace],
        ];
    }
}
