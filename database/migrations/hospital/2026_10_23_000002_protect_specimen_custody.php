<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';
    public function up(): void
    {
        DB::connection('hospital')->unprepared(<<<'SQL'
            ALTER TABLE specimens ADD CONSTRAINT specimens_rejected_without_code CHECK (status <> 'rejected' OR code IS NULL);
            CREATE OR REPLACE FUNCTION protect_specimen_custody() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'La custodia de muestras es inmutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER specimens_custody BEFORE UPDATE OR DELETE ON specimens
                FOR EACH ROW EXECUTE FUNCTION protect_specimen_custody();
            SQL);
    }
    public function down(): void
    {
        DB::connection('hospital')->unprepared('DROP TRIGGER specimens_custody ON specimens; DROP FUNCTION protect_specimen_custody(); ALTER TABLE specimens DROP CONSTRAINT specimens_rejected_without_code;');
    }
};
