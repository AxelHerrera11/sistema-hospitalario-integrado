<?php

declare(strict_types=1);

namespace Tests\Feature\SalasCamas;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use MicroHis\SalasCamas\Application\{BedRepository, CatalogService, PersistenceFailure, WardRepository};
use MicroHis\SalasCamas\Domain\{Bed, Ward};
use Tests\Feature\Rbac\RbacTestCase;
use Tests\Feature\Rbac\TestIdentity;

require_once dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'micro-his'
    . DIRECTORY_SEPARATOR . 'salas-camas' . DIRECTORY_SEPARATOR . 'bootstrap.php';

final class WardCatalogEndpointTest extends RbacTestCase
{
    private FakeWardCatalogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new InMemoryRbacStore([
            'CatalogReader' => ['camas.ver'],
            'WithoutPermission' => [],
        ]);
        $this->store->grant('reader', 'hospital-a', 'CatalogReader');
        $this->store->grant('other-user', 'hospital-a', 'WithoutPermission');

        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $this->store);
        }

        $this->repository = new FakeWardCatalogRepository([
            new Ward('ward-1', 'UCI', 'Cuidados intensivos', 'Piso 2', 'active'),
        ]);
        $this->app->instance(
            CatalogService::class,
            new CatalogService($this->repository, $this->repository),
        );

        TestIdentity::$user = new GenericUser(['id' => 'reader']);
        TestIdentity::$hospital = 'hospital-a';
    }

    public function test_route_is_registered_and_returns_only_catalog_fields(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/v1/wards', 'GET'));

        $this->assertContains('tenant', $route->gatherMiddleware());
        $this->assertContains('jwt.auth', $route->gatherMiddleware());
        $this->assertContains('rbac:camas.ver', $route->gatherMiddleware());

        $response = $this->getJson('/api/v1/wards', ['X-Request-ID' => 'asii06-test-1']);

        $response->assertOk()
            ->assertJsonPath('data.0.ward_id', 'ward-1')
            ->assertJsonPath('data.0.code', 'UCI')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('meta.request_id', 'asii06-test-1');
        $this->assertSame(
            ['ward_id', 'code', 'name', 'location', 'status'],
            array_keys($response->json('data.0')),
        );
    }

    public function test_empty_catalog_returns_an_empty_list(): void
    {
        $this->repository->wards = [];

        $this->getJson('/api/v1/wards')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.count', 0);
    }

    public function test_request_without_permission_is_denied(): void
    {
        TestIdentity::$user = new GenericUser(['id' => 'other-user']);

        $this->getJson('/api/v1/wards')
            ->assertForbidden()
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_anonymous_request_is_denied(): void
    {
        TestIdentity::$user = null;

        $this->getJson('/api/v1/wards')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'AUTH_REQUIRED');
    }

    public function test_persistence_error_returns_safe_unavailable_response(): void
    {
        $this->repository->fail = true;

        $this->getJson('/api/v1/wards')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'WARD_CATALOG_UNAVAILABLE')
            ->assertDontSee('private persistence detail');
    }

    public function test_unavailable_hospital_connection_returns_safe_unavailable_response(): void
    {
        config(['database.connections.hospital.database' => 'missing_asii06_' . bin2hex(random_bytes(6))]);
        DB::purge('hospital');
        $this->app->forgetInstance(CatalogService::class);

        $this->getJson('/api/v1/wards')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'WARD_CATALOG_UNAVAILABLE')
            ->assertDontSee('missing_asii06');
    }
}

final class FakeWardCatalogRepository implements WardRepository, BedRepository
{
    public bool $fail = false;

    public function __construct(public array $wards)
    {
    }

    public function listAll(): array
    {
        if ($this->fail) {
            throw new PersistenceFailure('private persistence detail');
        }

        return $this->wards;
    }

    public function findByCode(string $code): ?Ward
    {
        return null;
    }

    public function insertWard(Ward $ward): Ward
    {
        throw new \LogicException('Not used by the read-only endpoint test.');
    }

    public function insertBed(Bed $bed): Bed
    {
        throw new \LogicException('Not used by the read-only endpoint test.');
    }
}
