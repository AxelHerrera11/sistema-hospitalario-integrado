<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Slice H7, entidad `critical_alert`: alertas críticas de laboratorio y su acuse (ASII-21).
 *
 * Número 000660: dentro del bloque de laboratorio (000600, openspec .../tasks.md, tarea 3.6), después del catálogo
 * `lab_tests` (ASII-17), del que toma la versión de la prueba. `lab_result_version_id` no lleva FK porque la tabla de
 * resultados (ASII-19) aún no existe; su UNIQUE da la idempotencia: una alerta por resultado validado. Las reglas replican
 * las del dominio del micro-HIS (CriticalAlert): sin destinatario la alerta va a la jefatura del servicio (nivel ≥ 1), el
 * acuse registra quién y cuándo, y una alerta acusada ya no cambia.
 */
return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE critical_alerts (
                critical_alert_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                lab_result_version_id uuid NOT NULL CONSTRAINT critical_alerts_result_unique UNIQUE,
                lab_test_id uuid NOT NULL REFERENCES lab_tests(lab_test_id) ON DELETE RESTRICT,
                local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                measured_value numeric NOT NULL,
                direction text NOT NULL CONSTRAINT critical_alerts_direction_check CHECK (direction IN ('low', 'high')),
                current_recipient_user_ref_id uuid REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                escalation_level smallint NOT NULL DEFAULT 0 CONSTRAINT critical_alerts_level_check CHECK (escalation_level BETWEEN 0 AND 2),
                detected_at timestamptz NOT NULL DEFAULT now(),
                due_at timestamptz NOT NULL,
                status text NOT NULL DEFAULT 'open' CONSTRAINT critical_alerts_status_check CHECK (status IN ('open', 'acknowledged')),
                acknowledged_by_user_ref_id uuid REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                acknowledged_at timestamptz,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT critical_alerts_service_head_check CHECK (current_recipient_user_ref_id IS NOT NULL OR escalation_level >= 1),
                CONSTRAINT critical_alerts_acknowledgement_check CHECK ((status = 'acknowledged') = (acknowledged_by_user_ref_id IS NOT NULL AND acknowledged_at IS NOT NULL))
            );
            CREATE INDEX critical_alerts_pending_recipient_index ON critical_alerts (current_recipient_user_ref_id, due_at) WHERE status = 'open';
            CREATE INDEX critical_alerts_lab_test_id_index ON critical_alerts (lab_test_id);
            CREATE INDEX critical_alerts_local_patient_id_index ON critical_alerts (local_patient_id);
            CREATE INDEX critical_alerts_care_episode_id_index ON critical_alerts (care_episode_id);
            CREATE INDEX critical_alerts_acknowledged_by_index ON critical_alerts (acknowledged_by_user_ref_id);
            CREATE OR REPLACE FUNCTION enforce_critical_alert_integrity() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN RAISE EXCEPTION 'critical_alerts_delete_protection'; END IF;
                IF NOT EXISTS (SELECT 1 FROM care_episodes e WHERE e.care_episode_id = NEW.care_episode_id AND e.local_patient_id = NEW.local_patient_id) THEN
                    RAISE EXCEPTION 'critical_alerts_episode_patient_trigger';
                END IF;
                IF TG_OP = 'UPDATE' AND OLD.status = 'acknowledged' THEN RAISE EXCEPTION 'critical_alerts_acknowledged_immutable'; END IF;
                IF TG_OP = 'UPDATE' AND (NEW.lab_result_version_id, NEW.lab_test_id, NEW.local_patient_id, NEW.care_episode_id, NEW.measured_value, NEW.direction)
                    IS DISTINCT FROM (OLD.lab_result_version_id, OLD.lab_test_id, OLD.local_patient_id, OLD.care_episode_id, OLD.measured_value, OLD.direction) THEN
                    RAISE EXCEPTION 'critical_alerts_detection_immutable';
                END IF;
                RETURN COALESCE(NEW, OLD);
            END; $$;
            CREATE TRIGGER critical_alerts_integrity BEFORE INSERT OR UPDATE OR DELETE ON critical_alerts FOR EACH ROW EXECUTE FUNCTION enforce_critical_alert_integrity();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER critical_alerts_integrity ON critical_alerts; DROP FUNCTION enforce_critical_alert_integrity(); DROP TABLE critical_alerts;');
    }
};
