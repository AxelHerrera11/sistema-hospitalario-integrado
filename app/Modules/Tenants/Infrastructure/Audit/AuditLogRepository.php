<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Audit;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

final class AuditLogRepository implements AuditLogRepositoryInterface
{
    private const REDACTED_KEYS = '/password|token|secret|authorization|cookie|credential|mfa_code|otp|private_key|api_key/i';

    public function __construct(private readonly string $connection = 'central') {}

    public function logRaw(string $eventName, array $payload, ?string $severity = null): void
    {
        $actorId = (string) ($payload['user_id'] ?? $payload['actor_id'] ?? 'system');
        $hospitalId = (string) ($payload['hospital_id'] ?? $payload['tenant_id'] ?? 'unscoped');
        $detail = $this->redact($payload);
        $detail['user_id'] = $payload['user_id'] ?? null;
        $detail['hospital_id'] = $hospitalId;
        $detail['tenant_id'] = $payload['tenant_id'] ?? $hospitalId;
        $detail['permission'] = $payload['permission'] ?? null;
        $detail['reason'] = $payload['reason'] ?? null;
        $detail['trace_id'] = $payload['trace_id'] ?? null;

        if ($severity !== null) {
            $detail['severity'] = $severity;
        }

        try {
            $detail = $this->canonicalize($detail);
            $encoded = json_encode(
                $detail,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('No fue posible serializar el detalle de auditoría.', 0, $exception);
        }

        DB::connection($this->connection)->transaction(function () use (
            $eventName,
            $actorId,
            $hospitalId,
            $detail,
            $encoded,
            $payload,
        ): void {
            $database = DB::connection($this->connection);
            $state = $database->table('audit_chain_state')->where('chain_id', 1)->lockForUpdate()->first();

            if ($state === null) {
                throw new RuntimeException('No existe el estado de la cadena de auditoría.');
            }

            $eventId = (string) Str::uuid();
            $occurredAt = now()->utc();
            $timestamp = $occurredAt->format('Y-m-d\TH:i:s.u\Z');
            $result = isset($payload['result']) ? (string) $payload['result'] : null;
            $previousHash = (string) $state->head_hash;
            $eventHash = $this->eventHash(
                $previousHash,
                $eventId,
                $timestamp,
                $eventName,
                $actorId,
                $hospitalId,
                $detail,
                isset($payload['trace_id']) ? (string) $payload['trace_id'] : null,
                $result,
            );

            $database->table('audit_log')->insert([
                'event_id' => $eventId,
                'occurred_at' => $occurredAt,
                'event' => $eventName,
                'actor_id' => $actorId,
                'hospital_id' => $hospitalId,
                'detail' => $encoded,
                'trace_id' => $payload['trace_id'] ?? null,
                'result' => $result,
                'previous_hash' => $previousHash,
                'event_hash' => $eventHash,
            ]);

            if ($database->table('audit_chain_state')->where('chain_id', 1)
                ->update(['head_hash' => $eventHash]) !== 1) {
                throw new RuntimeException('No fue posible avanzar la cadena de auditoría.');
            }
        });
    }

    private function redact(array $value): array
    {
        $safe = [];
        foreach ($value as $key => $item) {
            if (preg_match(self::REDACTED_KEYS, (string) $key) === 1) {
                continue;
            }

            $safe[$key] = is_array($item) ? $this->redact($item) : $item;
        }

        return $safe;
    }

    private function canonicalize(array $value): array
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

    private function eventHash(
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
        try {
            $payload = json_encode([
                'event_id' => $eventId,
                'occurred_at' => $occurredAt,
                'event' => $event,
                'actor_id' => $actorId,
                'hospital_id' => $hospitalId,
                'detail' => $detail,
                'trace_id' => $traceId,
                'result' => $result,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('No fue posible calcular la cadena de auditoría.', 0, $exception);
        }

        return hash('sha256', $previousHash.'|'.$payload.'|'.$occurredAt);
    }
}
