<?php

declare(strict_types=1);

namespace Tests\Feature\SalasCamas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MicroHis\SalasCamas\Application\{BedRepository, CatalogService, WardRepository};
use MicroHis\SalasCamas\Persistence\{PdoBedRepository, PdoWardRepository};
use Tests\Feature\Tenants\TenantAuthTestCase;

final class CatalogAuthenticatedIntegrationTest extends TenantAuthTestCase
{
    public function test_authenticated_http_catalog_uses_real_hospital_repositories(): void
    {
        $this->assertSame('testing', app()->environment());
        $connection = DB::connection('hospital');
        $this->assertSame('his_hospital_test', $connection->getDatabaseName());
        $this->assertSame('127.0.0.1', config('database.connections.hospital.host'));
        $this->assertSame('55433', (string) config('database.connections.hospital.port'));
        $pdo = $connection->getPdo();
        $pdo->beginTransaction();
        $code = 'S16-'.Str::uuid();
        try {
            $this->assertInstanceOf(PdoWardRepository::class, app(WardRepository::class));
            $this->assertInstanceOf(PdoBedRepository::class, app(BedRepository::class));
            $service = app(CatalogService::class);
            $ward = $service->createWard(['code' => $code, 'name' => 'Sala sintética', 'location' => 'Test']);
            $empty = $service->createWard(['code' => $code.'-EMPTY', 'name' => 'Vacía', 'location' => 'Test']);
            $bed = $service->createBed($code, ['code' => 'B-1', 'type' => 'standard']);
            $this->addMembership($this->userId, $this->hospitalA, 'superadmin');
            config(['tenants.hospital_database_tenant_id' => $this->hospitalA]);
            $login = $this->login()->assertOk();
            $headers = ['Authorization' => 'Bearer '.$this->tokenFrom($login), 'X-Tenant-ID' => $this->hospitalA];

            $this->getJson('/api/v1/wards', $headers)->assertOk()
                ->assertJsonFragment(['ward_id' => $ward->id, 'code' => $code, 'status' => 'active']);
            $this->getJson('/api/v1/wards/'.$ward->id.'/beds', $headers)->assertOk()
                ->assertJsonCount(1, 'data')->assertJsonPath('data.0.bed_id', $bed->id)
                ->assertJsonPath('data.0.ward_id', $ward->id)
                ->assertJsonPath('data.0.status', 'available')->assertJsonPath('meta.count', 1);
            $this->getJson('/api/v1/wards/'.$empty->id.'/beds', $headers)->assertOk()
                ->assertJsonPath('data', [])->assertJsonPath('meta.count', 0);
            $this->getJson('/api/v1/wards/'.Str::uuid().'/beds', $headers)->assertNotFound()
                ->assertJsonPath('error.code', 'WARD_NOT_FOUND');
            $this->getJson('/api/v1/wards', ['X-Tenant-ID' => $this->hospitalA])
                ->assertUnauthorized()->assertJsonPath('error', 'TOKEN_MISSING');
            config(['tenants.hospital_database_tenant_id' => $this->hospitalB]);
            $this->getJson('/api/v1/wards', $headers)->assertForbidden()
                ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH')->assertDontSee($code);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
        $query = $pdo->prepare('SELECT count(*) FROM wards WHERE code IN (?, ?)');
        $query->execute([$code, $code.'-EMPTY']);
        $this->assertSame(0, (int) $query->fetchColumn());
    }
}
