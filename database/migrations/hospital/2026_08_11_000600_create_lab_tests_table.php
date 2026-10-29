<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Slice H6, entidad `lab_test`: catálogo versionado de pruebas de laboratorio del hospital (ASII-17).
 *
 * Número 000600: el que reserva la especificación para el laboratorio (openspec .../tasks.md, tarea 3.6), dividida por
 * dueño: aquí solo el catálogo; órdenes, muestras, resultados y alertas las crean sus módulos. Las reglas replican las del
 * dominio del micro-HIS (LabTest::register): código en mayúsculas, volumen mínimo positivo, rangos coherentes solo para
 * resultados numéricos y una sola versión activa vigente por código. Los ítems de orden referencian el UUID de la versión.
 */
return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE lab_tests (
                lab_test_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                code text NOT NULL CONSTRAINT lab_tests_code_check CHECK (code ~ '^[A-Z0-9][A-Z0-9._-]{1,31}$'),
                name text NOT NULL CONSTRAINT lab_tests_name_check CHECK (name = btrim(name) AND name <> ''),
                specimen_type text NOT NULL CONSTRAINT lab_tests_specimen_type_check CHECK (btrim(specimen_type) <> ''),
                min_volume_ml numeric NOT NULL CONSTRAINT lab_tests_min_volume_check CHECK (min_volume_ml > 0),
                result_type text NOT NULL CONSTRAINT lab_tests_result_type_check CHECK (result_type IN ('numeric', 'qualitative', 'text')),
                unit text,
                reference_low numeric,
                reference_high numeric,
                critical_low numeric,
                critical_high numeric,
                version_no integer NOT NULL CONSTRAINT lab_tests_version_check CHECK (version_no > 0),
                valid_from date NOT NULL,
                valid_to date,
                status text NOT NULL DEFAULT 'active' CONSTRAINT lab_tests_status_check CHECK (status IN ('active', 'inactive')),
                created_by_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT lab_tests_numeric_definition_check CHECK (result_type <> 'numeric' OR (unit IS NOT NULL AND btrim(unit) <> ''
                    AND num_nonnulls(reference_low, reference_high, critical_low, critical_high) = 4
                    AND critical_low < reference_low AND reference_low <= reference_high AND reference_high < critical_high)),
                CONSTRAINT lab_tests_non_numeric_ranges_check CHECK (result_type = 'numeric' OR num_nonnulls(reference_low, reference_high, critical_low, critical_high) = 0),
                CONSTRAINT lab_tests_validity_check CHECK (valid_to IS NULL OR valid_to > valid_from),
                CONSTRAINT lab_tests_code_version_unique UNIQUE (code, version_no),
                CONSTRAINT lab_tests_active_validity_exclusion EXCLUDE USING gist (code WITH =, daterange(valid_from, valid_to, '[)') WITH &&) WHERE (status = 'active')
            );
            CREATE INDEX lab_tests_created_by_user_ref_id_index ON lab_tests (created_by_user_ref_id);
            CREATE OR REPLACE FUNCTION preserve_lab_test_version() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'lab_tests_delete_protection'; END; $$;
            CREATE TRIGGER lab_tests_retention BEFORE DELETE ON lab_tests FOR EACH ROW EXECUTE FUNCTION preserve_lab_test_version();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER lab_tests_retention ON lab_tests; DROP FUNCTION preserve_lab_test_version(); DROP TABLE lab_tests;');
    }
};
