<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo CIE-10 del esquema HOSPITAL (ASII-09).
 *
 * Entidad de referencia fuera de las 27 de la especificación, aprobada por el grupo
 * el 30/10/2026 a pedido de Notas SOAP (ASII-11). La consume `Icd10Catalog`; no hay
 * FK desde `diagnoses` porque esa tabla conserva su propio snapshot de código y
 * descripción para no romper el histórico cuando un código se desactiva.
 */
return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE icd10_codes (
                code varchar(10) PRIMARY KEY,
                description text NOT NULL,
                active boolean NOT NULL DEFAULT true,
                CONSTRAINT icd10_codes_code_check CHECK (code = upper(btrim(code)) AND code ~ '^[A-Z][0-9]{2}(\.[0-9A-Z]{1,4})?$'),
                CONSTRAINT icd10_codes_description_check CHECK (description = btrim(description) AND description <> '')
            );
            COMMENT ON TABLE icd10_codes IS 'Catálogo CIE-10 (ASII-09). Fuente: OMS/PAHO CIE-10 2019. Solo referencia; diagnoses conserva su snapshot.';
            COMMENT ON COLUMN icd10_codes.code IS 'Código CIE-10 normalizado en mayúsculas, con punto opcional.';
            COMMENT ON COLUMN icd10_codes.active IS 'Vigencia del código; boolean, nunca entero.';
            CREATE INDEX icd10_codes_active_index ON icd10_codes (active) WHERE active;
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE icd10_codes;');
    }
};
