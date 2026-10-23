<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Presentation;

/**
 * Respuesta HTTP como arreglo simple, igual que AuditHttpHandler de Gobernanza.
 * Errores con el formato de RBAC: {"code": ..., "trace_id": ...} (+ detalle opcional).
 */
final class HttpResponse
{
    /** @return array{status: int, body: array<string, mixed>, headers: array<string, string>} */
    public static function json(int $status, array $body, string $trace): array
    {
        return [
            'status' => $status,
            'body' => $body + ['trace_id' => $trace],
            'headers' => ['Cache-Control' => 'no-store, private', 'X-Request-ID' => $trace],
        ];
    }

    /** @return array{status: int, body: array<string, mixed>, headers: array<string, string>} */
    public static function error(int $status, string $code, string $trace, array $detail = []): array
    {
        return self::json($status, ['code' => $code] + $detail, $trace);
    }

    /** Reutiliza X-Request-ID si tiene formato seguro; si no, genera uno. */
    public static function correlation(mixed $value): string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$/D', $value) === 1
            ? $value
            : 'req-'.bin2hex(random_bytes(8));
    }
}
