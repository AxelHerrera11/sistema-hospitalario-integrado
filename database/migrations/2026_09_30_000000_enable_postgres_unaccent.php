<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Habilita la extensión unaccent de PostgreSQL para búsquedas sin acentos
 * ("maria" encuentra "María"). En SQLite/MySQL no hace nada.
 * unaccent es una extensión "trusted" (PG 13+): basta con ser dueño de la BD.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP EXTENSION IF EXISTS unaccent');
        }
    }
};
