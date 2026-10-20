<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correctivo de consulta de `audit_log` (CENTRAL).
 *
 * Convierte la propuesta 001 de Gobernanza y Auditoria en una migracion reversible:
 * cuatro indices compuestos para las consultas de gobernanza y una restriccion
 * sobre los valores admitidos de `result`.
 *
 * Nota de implementacion: los indices se crean con `CREATE INDEX` y no con
 * `CREATE INDEX CONCURRENTLY`, porque Laravel envuelve cada migracion en una
 * transicion y PostgreSQL no admite `CONCURRENTLY` dentro de una transaccion.
 */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE INDEX audit_log_hospital_occurred_index
                ON audit_log (hospital_id, occurred_at DESC, event_id DESC);

            CREATE INDEX audit_log_hospital_actor_occurred_index
                ON audit_log (hospital_id, actor_id, occurred_at DESC);

            CREATE INDEX audit_log_hospital_event_occurred_index
                ON audit_log (hospital_id, event text_pattern_ops, occurred_at DESC);

            CREATE INDEX audit_log_hospital_result_occurred_index
                ON audit_log (hospital_id, result, occurred_at DESC);

            ALTER TABLE audit_log
                ADD CONSTRAINT audit_log_result_check
                CHECK (result IS NULL OR result IN ('success', 'failure', 'denied')) NOT VALID;
            SQL);

        // La tabla es de solo insercion: una fila que incumpla la restriccion ya
        // no se podria corregir con UPDATE, porque el trigger append-only lo
        // rechaza. Por eso la constraint se valida en cuanto los datos lo
        // permiten y, si alguna fila existente la incumple, queda NOT VALID
        // documentado en lugar de bloquear el despliegue.
        DB::connection($this->connection)->unprepared(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM audit_log
                    WHERE result IS NOT NULL AND result NOT IN ('success', 'failure', 'denied')
                ) THEN
                    ALTER TABLE audit_log VALIDATE CONSTRAINT audit_log_result_check;
                END IF;
            END $$;
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            ALTER TABLE audit_log DROP CONSTRAINT IF EXISTS audit_log_result_check;
            DROP INDEX IF EXISTS audit_log_hospital_result_occurred_index;
            DROP INDEX IF EXISTS audit_log_hospital_event_occurred_index;
            DROP INDEX IF EXISTS audit_log_hospital_actor_occurred_index;
            DROP INDEX IF EXISTS audit_log_hospital_occurred_index;
            SQL);
    }
};
