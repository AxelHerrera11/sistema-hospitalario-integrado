<?php

declare(strict_types=1);

namespace Tests\Feature\SalasCamas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MicroHis\SalasCamas\Application\BedRepository;
use MicroHis\SalasCamas\Application\CatalogService;
use MicroHis\SalasCamas\Application\WardRepository;
use MicroHis\SalasCamas\Persistence\PdoBedRepository;
use MicroHis\SalasCamas\Persistence\PdoWardRepository;
use PDO;
use Tests\TestCase;

final class CatalogLaravelIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment());
        $this->assertSame('his_hospital_test', DB::connection('hospital')->getDatabaseName());
        $this->assertSame('55433', (string) config('database.connections.hospital.port'));
        $this->pdo = DB::connection('hospital')->getPdo();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }

    public function test_container_resolves_catalog_repositories_and_uses_hospital_postgresql(): void
    {
        $wards = $this->app->make(WardRepository::class);
        $beds = $this->app->make(BedRepository::class);
        $catalog = $this->app->make(CatalogService::class);

        $this->assertInstanceOf(PdoWardRepository::class, $wards);
        $this->assertInstanceOf(PdoBedRepository::class, $beds);

        $wardCode = 'INTEGRATION-'.Str::uuid();
        $ward = $catalog->createWard([
            'code' => $wardCode,
            'name' => 'Ward de prueba',
            'location' => 'Entorno de pruebas',
        ]);
        $bed = $catalog->createBed($wardCode, [
            'code' => 'BED-'.Str::uuid(),
            'type' => 'standard',
        ]);

        $this->assertNotNull($ward->id);
        $this->assertNotNull($bed->id);
        $this->assertSame($ward->id, $bed->wardId);
        $this->assertSame('available', $bed->status);
        $bedQuery = $this->pdo->prepare('SELECT ward_id FROM beds WHERE bed_id = :bed_id');
        $bedQuery->execute([':bed_id' => $bed->id]);
        $this->assertSame($ward->id, $bedQuery->fetchColumn());
        $this->assertSame(
            0,
            (int) $this->pdo->query("SELECT count(*) FROM information_schema.columns WHERE table_schema = 'public' AND table_name IN ('wards', 'beds') AND column_name = 'tenant_id'")->fetchColumn(),
        );

        $this->pdo->rollBack();
        $wardQuery = $this->pdo->prepare('SELECT count(*) FROM wards WHERE code = :code');
        $wardQuery->execute([':code' => $wardCode]);
        $this->assertSame(0, (int) $wardQuery->fetchColumn());
    }
}
