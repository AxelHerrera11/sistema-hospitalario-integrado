<?php

declare(strict_types=1);

namespace Tests\Feature\SalasCamas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MicroHis\SalasCamas\Application\BedRepository;
use MicroHis\SalasCamas\Application\CatalogService;
use MicroHis\SalasCamas\Application\WardRepository;
use MicroHis\SalasCamas\Domain\Ward;
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
        $bedCode = 'BED-'.Str::uuid();
        $ward = $catalog->createWard([
            'code' => $wardCode,
            'name' => 'Ward de prueba',
            'location' => 'Entorno de pruebas',
        ]);
        $bed = $catalog->createBed($wardCode, [
            'code' => $bedCode,
            'type' => 'standard',
        ]);
        $otherWardCode = 'INTEGRATION-'.Str::uuid();
        $otherBedCode = 'BED-'.Str::uuid();
        $otherWard = $catalog->createWard([
            'code' => $otherWardCode,
            'name' => 'Otra ward de prueba',
            'location' => 'Entorno de pruebas',
        ]);
        $otherBed = $catalog->createBed($otherWardCode, [
            'code' => $otherBedCode,
            'type' => 'standard',
        ]);

        $this->assertNotNull($ward->id);
        $this->assertNotNull($bed->id);
        $this->assertNotNull($otherWard->id);
        $this->assertNotNull($otherBed->id);
        $this->assertSame($ward->id, $bed->wardId);
        $this->assertSame($otherWard->id, $otherBed->wardId);
        $this->assertContains($ward->id, array_map(
            static fn (Ward $listed): ?string => $listed->id,
            $catalog->listWards(),
        ));
        $wardBeds = $catalog->listBedsForWard((string) $ward->id);
        $this->assertCount(1, $wardBeds);
        $this->assertSame($bed->id, $wardBeds[0]->id);
        $this->assertSame($ward->id, $wardBeds[0]->wardId);
        $this->assertNotSame($otherBed->id, $wardBeds[0]->id);
        $this->assertSame('available', $bed->status);
        $bedQuery = $this->pdo->prepare('SELECT ward_id FROM beds WHERE bed_id = :bed_id');
        $bedQuery->execute([':bed_id' => $bed->id]);
        $this->assertSame($ward->id, $bedQuery->fetchColumn());
        $this->assertSame(
            0,
            (int) $this->pdo->query("SELECT count(*) FROM information_schema.columns WHERE table_schema = 'public' AND table_name IN ('wards', 'beds') AND column_name = 'tenant_id'")->fetchColumn(),
        );

        $this->pdo->rollBack();
        foreach ([$wardCode, $otherWardCode] as $code) {
            $wardQuery = $this->pdo->prepare('SELECT count(*) FROM wards WHERE code = :code');
            $wardQuery->execute([':code' => $code]);
            $this->assertSame(0, (int) $wardQuery->fetchColumn());
        }
        foreach ([$bedCode, $otherBedCode] as $code) {
            $bedQuery = $this->pdo->prepare('SELECT count(*) FROM beds WHERE code = :code');
            $bedQuery->execute([':code' => $code]);
            $this->assertSame(0, (int) $bedQuery->fetchColumn());
        }
    }
}
