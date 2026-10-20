<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $connection = DB::connection($this->connection);
        $connection->unprepared(<<<'SQL'
            ALTER TABLE audit_log
                ADD COLUMN event_id uuid,
                ADD COLUMN occurred_at timestamptz,
                ADD COLUMN result text,
                ADD COLUMN previous_hash char(64),
                ADD COLUMN event_hash char(64);
            UPDATE audit_log
                SET event_id = gen_random_uuid(),
                    occurred_at = CURRENT_TIMESTAMP,
                    result = detail ->> 'result';
            ALTER TABLE audit_log
                ALTER COLUMN event_id SET NOT NULL,
                ALTER COLUMN event_id SET DEFAULT gen_random_uuid(),
                ALTER COLUMN occurred_at SET NOT NULL,
                ALTER COLUMN occurred_at SET DEFAULT clock_timestamp(),
                ADD CONSTRAINT audit_log_pkey PRIMARY KEY (event_id);
            CREATE TABLE audit_chain_state (
                chain_id smallint PRIMARY KEY CHECK (chain_id = 1),
                head_hash char(64) NOT NULL
            );
            INSERT INTO audit_chain_state (chain_id, head_hash)
                VALUES (1, repeat('0', 64));
            SQL);

        $previousHash = str_repeat('0', 64);
        foreach ($connection->table('audit_log')->orderBy('event_id')->get() as $row) {
            $occurredAt = Carbon::parse($row->occurred_at)
                ->utc()
                ->format('Y-m-d\TH:i:s.u\Z');
            $detail = $this->canonicalize(json_decode((string) $row->detail, true, 512, JSON_THROW_ON_ERROR));
            $hash = $this->eventHash(
                $previousHash,
                $row->event_id,
                $occurredAt,
                $row->event,
                $row->actor_id,
                $row->hospital_id,
                $detail,
                $row->trace_id,
                $row->result,
            );

            $connection->table('audit_log')->where('event_id', $row->event_id)->update([
                'previous_hash' => $previousHash,
                'event_hash' => $hash,
            ]);
            $previousHash = $hash;
        }

        $connection->table('audit_chain_state')->where('chain_id', 1)->update(['head_hash' => $previousHash]);
        $connection->unprepared(<<<'SQL'
            CREATE FUNCTION reject_audit_log_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'audit_log is append-only';
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER audit_log_reject_update_or_delete
                BEFORE UPDATE OR DELETE ON audit_log
                FOR EACH ROW EXECUTE FUNCTION reject_audit_log_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS audit_log_reject_update_or_delete ON audit_log;
            DROP FUNCTION IF EXISTS reject_audit_log_mutation();
            DROP TABLE IF EXISTS audit_chain_state;
            ALTER TABLE audit_log
                DROP CONSTRAINT IF EXISTS audit_log_pkey,
                DROP COLUMN IF EXISTS event_id,
                DROP COLUMN IF EXISTS occurred_at,
                DROP COLUMN IF EXISTS result,
                DROP COLUMN IF EXISTS previous_hash,
                DROP COLUMN IF EXISTS event_hash;
            SQL);
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
        return hash('sha256', $previousHash.'|'.json_encode([
            'event_id' => $eventId,
            'occurred_at' => $occurredAt,
            'event' => $event,
            'actor_id' => $actorId,
            'hospital_id' => $hospitalId,
            'detail' => $detail,
            'trace_id' => $traceId,
            'result' => $result,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR).'|'.$occurredAt);
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
};
