<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        $connection = DB::connection($this->connection);
        $schema = DB::connection($this->connection)->getSchemaBuilder();

        if (! $schema->hasColumn('lab_orders', 'idempotency_request_hash')) {
            $connection->statement(
                "ALTER TABLE lab_orders ADD COLUMN idempotency_request_hash text NOT NULL DEFAULT repeat('0', 64)"
            );
            $connection->statement(
                'ALTER TABLE lab_orders ALTER COLUMN idempotency_request_hash DROP DEFAULT'
            );
        }

        // Older local databases used lowercase priorities while the API contract
        // and current controller use the canonical uppercase values.
        $connection->statement('ALTER TABLE lab_orders DROP CONSTRAINT IF EXISTS lab_orders_priority_check');
        $connection->statement(
            "ALTER TABLE lab_orders ADD CONSTRAINT lab_orders_priority_check CHECK (priority IN ('ROUTINE', 'URGENT', 'STAT'))"
        );
        $connection->statement('ALTER TABLE lab_orders DROP CONSTRAINT IF EXISTS lab_orders_status_check');
        $connection->statement(
            "ALTER TABLE lab_orders ADD CONSTRAINT lab_orders_status_check CHECK (status IN ('REQUESTED', 'COLLECTED', 'IN_PROCESS', 'RESULTED', 'VALIDATED', 'CANCELLED'))"
        );
    }

    public function down(): void
    {
        $schema = DB::connection($this->connection)->getSchemaBuilder();

        if ($schema->hasColumn('lab_orders', 'idempotency_request_hash')) {
            $schema->table('lab_orders', static function ($table): void {
                $table->dropColumn('idempotency_request_hash');
            });
        }
    }
};
