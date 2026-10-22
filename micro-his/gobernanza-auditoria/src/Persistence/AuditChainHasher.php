<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Persistence;

/**
 * Cadena de hashes SHA-256 de la tabla audit_log.
 *
 * Reproduce el formato de App\Modules\Tenants\Infrastructure\Audit\AuditLogRepository
 * (rama feature/asii-25-semana-16-tenants-auditoria) para que ambos escritores
 * mantengan una sola cadena verificable:
 *
 *   sha256(previous_hash | json(event_id, occurred_at, event, actor_id,
 *          hospital_id, detail canónico, trace_id, result) | occurred_at)
 *
 * La cadena hace evidente una alteración posterior; no impide por sí misma
 * modificar filas (eso lo hace el trigger append-only de la base).
 */
final class AuditChainHasher
{
    public const GENESIS = '0000000000000000000000000000000000000000000000000000000000000000';
    public const TIME_FORMAT = 'Y-m-d\TH:i:s.u\Z';
    private const JSON = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    /** @param array<mixed> $detail */
    public function hash(
        string $previousHash,
        string $eventId,
        string $occurredAt,
        string $event,
        string $actorId,
        string $hospitalId,
        array $detail,
        ?string $traceId,
        ?string $result,
    ): string {
        $payload = json_encode([
            'event_id' => $eventId,
            'occurred_at' => $occurredAt,
            'event' => $event,
            'actor_id' => $actorId,
            'hospital_id' => $hospitalId,
            'detail' => $this->canonicalize($detail),
            'trace_id' => $traceId,
            'result' => $result,
        ], self::JSON);

        return hash('sha256', $previousHash . '|' . $payload . '|' . $occurredAt);
    }

    /** @param array<mixed> $detail */
    public function encode(array $detail): string
    {
        return json_encode($this->canonicalize($detail), self::JSON);
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    public function canonicalize(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(fn ($item) => is_array($item) ? $this->canonicalize($item) : $item, $value);
        }
        ksort($value);
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        return $value;
    }
}
