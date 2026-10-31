<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\NotasSoap\Application\CatalogUnavailable;
use MicroHis\NotasSoap\Persistence\PdoIcd10Catalog;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * El adaptador del catálogo CIE-10 de Notas SOAP contra PostgreSQL real. `icd10_codes` la crea ASII-09; la prueba la declara con
 * `IF NOT EXISTS` y la misma forma (`active boolean`) para que valga antes y después de esa migración.
 */
#[Group('database-integrity')]
class NotasSoapIcd10CatalogPostgresTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        DB::connection('hospital')->unprepared("CREATE TABLE IF NOT EXISTS icd10_codes (code varchar(10) PRIMARY KEY, description text NOT NULL, active boolean NOT NULL DEFAULT true);
            INSERT INTO icd10_codes VALUES ('J18.9', 'Neumonía, no especificada', true), ('A00', 'Cólera', false) ON CONFLICT (code) DO NOTHING");
    }

    public function test_an_active_code_returns_its_description(): void
    {
        $this->assertSame('Neumonía, no especificada', $this->catalog()->description('J18.9'));
    }

    public function test_an_inactive_or_unknown_code_returns_null(): void
    {
        $this->assertNull($this->catalog()->description('A00'));
        $this->assertNull($this->catalog()->description('Q99.9'));
    }

    public function test_the_seed_minimum_loads_and_every_code_is_readable(): void
    {
        $db = DB::connection('hospital');
        $rows = array_map('str_getcsv', array_slice(file(base_path('micro-his/notas-soap/database/icd10-seed-minimo.csv'), FILE_IGNORE_NEW_LINES), 1));
        foreach ($rows as [$code, $description, $active]) {
            $db->insert('INSERT INTO icd10_codes (code, description, active) VALUES (?, ?, ?::boolean) ON CONFLICT (code) DO NOTHING', [$code, $description, $active]);
        }

        $catalog = $this->catalog();
        $this->assertCount(20, $rows);
        foreach ($rows as [$code, $description]) {
            $this->assertSame($description, $catalog->description($code), $code);
        }
    }

    public function test_a_migrated_but_empty_catalog_is_unavailable_and_not_an_invalid_code(): void
    {
        DB::connection('hospital')->delete('DELETE FROM icd10_codes');

        $this->expectException(CatalogUnavailable::class);
        $this->catalog()->description('J18.9');
    }

    private function catalog(): PdoIcd10Catalog
    {
        return new PdoIcd10Catalog(DB::connection('hospital')->getPdo());
    }
}
