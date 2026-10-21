<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Slice H5, entidad `medication`: catálogo local de medicamentos del hospital (ASII-14).
 *
 * Número 000500: el que reserva la especificación para H5 (openspec .../tasks.md, tarea 3.5).
 * La entidad `prescription` del mismo slice pertenece a Prescripciones y no se crea aquí.
 * Las reglas replican las del dominio del micro-HIS: código normalizado en mayúsculas,
 * textos recortados de 2 a 120 caracteres y vigencia `active`/`inactive`. La vigencia es
 * del registro del catálogo; no representa vencimiento de lotes.
 */
return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE medications (
                medication_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                code text NOT NULL CONSTRAINT medications_code_format_check CHECK (code ~ '^[A-Z0-9][A-Z0-9-]{1,19}$'),
                name text NOT NULL CONSTRAINT medications_name_check CHECK (name = btrim(name) AND char_length(name) BETWEEN 2 AND 120),
                active_ingredient text NOT NULL CONSTRAINT medications_active_ingredient_check CHECK (active_ingredient = btrim(active_ingredient) AND char_length(active_ingredient) BETWEEN 2 AND 120),
                pharmacological_group text NOT NULL CONSTRAINT medications_pharmacological_group_check CHECK (pharmacological_group = btrim(pharmacological_group) AND char_length(pharmacological_group) BETWEEN 2 AND 120),
                validity text NOT NULL DEFAULT 'active' CONSTRAINT medications_validity_check CHECK (validity IN ('active', 'inactive')),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT medications_code_unique UNIQUE (code)
            );
            COMMENT ON TABLE medications IS 'Slice H5: catálogo de medicamentos del hospital (ASII-14). validity es la vigencia del registro, no el vencimiento de lotes.';
            CREATE INDEX medications_validity_index ON medications (validity);
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE medications;');
    }
};
