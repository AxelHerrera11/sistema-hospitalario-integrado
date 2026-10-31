<?php

declare(strict_types=1);

namespace Tests\Feature\CatalogoLab;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Testing\TestResponse;
use MicroHis\CatalogoLab\Application\LabTestRepository;
use MicroHis\CatalogoLab\Persistence\InMemoryLabTestRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Semana 17: permisos por rol contra config/rbac.php y entradas hostiles (amenazas L01–L12 de docs/catalogo-pruebas-lab/semana-17). */
final class LabTestSecurityTest extends TestCase
{
    private InMemoryLabTestRepository $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', 'test.hospital-tenant');
        $this->app->instance('test.hospital-tenant', new class
        {
            public function handle($request, $next)
            {
                $request->attributes->set('tenant', new Hospital(['hospital_id' => TrasladosTestIdentity::$hospital]));

                return $next($request);
            }
        });
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity);
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity);
        $this->hospital = new InMemoryLabTestRepository;
        $this->app->instance(LabTestRepository::class, $this->hospital);
        $this->app->instance(ResolutorActorLocal::class, new class implements ResolutorActorLocal
        {
            public function referencia(string $centralUserId): ?string
            {
                return $centralUserId === '00000000-0000-4000-8000-00000000c0a1' ? '00000000-0000-4000-8000-0000000000f1' : null;
            }
        });
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    private function registrar(array $campos = []): TestResponse
    {
        return $this->postJson('/api/v1/lab-tests', $campos + ['code' => 'GLU', 'name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 0.5,
            'result_type' => 'numeric', 'unit' => 'mg/dL', 'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400,
            'valid_from' => now()->toDateString()]);
    }

    public function test_every_role_gets_exactly_what_the_rbac_matrix_grants(): void
    {
        $permitidos = ['ver' => [], 'administrar' => []];
        foreach (array_keys(config('rbac.roles')) as $rol) {
            TrasladosTestIdentity::como($rol);
            $permisos = config("rbac.roles.{$rol}");
            $lectura = $this->getJson('/api/v1/lab-tests');
            $escritura = $this->registrar(['code' => 'R-'.strtoupper(substr($rol, 0, 8))]);
            in_array('laboratorio.catalogo.ver', $permisos, true) ? $lectura->assertOk() : $lectura->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
            in_array('laboratorio.catalogo.administrar', $permisos, true) ? $escritura->assertStatus(201) : $escritura->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
            $lectura->isOk() && $permitidos['ver'][] = $rol;
            $escritura->status() === 201 && $permitidos['administrar'][] = $rol;
        }
        // admin_hospital sigue en 'pending' para administrar; si alguien lo activa, esta prueba obliga a revisar L03.
        $this->assertSame(['superadmin', 'medico', 'tecnico_laboratorio', 'bioquimico'], $permitidos['ver']);
        $this->assertSame(['superadmin'], $permitidos['administrar']);
        $this->assertCount(1, $this->hospital->rows);
    }

    public function test_body_cannot_choose_author_identifier_or_version(): void
    {
        TrasladosTestIdentity::como('superadmin');
        $respuesta = $this->registrar(['created_by_user_ref_id' => 'suplantado', 'lab_test_id' => '00000000-0000-4000-8000-0000000000ee',
            'version_no' => 99, 'status' => 'inactive'])->assertStatus(201)->assertJsonPath('data.version_no', 1);
        $this->assertNotSame('00000000-0000-4000-8000-0000000000ee', $respuesta->json('data.lab_test_id'));
        $this->assertSame(['prueba_lab.registrada', '00000000-0000-4000-8000-0000000000f1', $respuesta->json('data.lab_test_id')], $this->hospital->audit[0]);
    }

    public function test_hostile_input_never_answers_500(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('superadmin');
        $this->registrar(['unit' => ['mg/dL']])->assertStatus(422)->assertJsonPath('error.field', 'unit');
        $this->registrar(['unit' => 5])->assertStatus(422)->assertJsonPath('error.field', 'unit');
        $this->registrar(['critical_high' => '1e400'])->assertStatus(422)->assertJsonPath('error.code', 'RANGES_INCOHERENT');
        $this->registrar(['name' => str_repeat('n', 100_000)])->assertStatus(422)->assertJsonPath('error.code', 'TEXT_TOO_LONG');
        $this->registrar(['unit' => str_repeat('u', 33)])->assertStatus(422)->assertJsonPath('error.field', 'unit');
        $this->registrar(['code' => ['GLU']])->assertStatus(422)->assertJsonPath('error.field', 'code');
        $this->getJson('/api/v1/lab-tests?search[]=x')->assertOk();
        $this->getJson('/api/v1/lab-tests?date[]=2026-10-28')->assertStatus(422)->assertJsonPath('error.code', 'DATE_INVALID');
        $this->assertSame([], $this->hospital->rows);
        $this->registrar(['name' => str_repeat('é', 160)])->assertStatus(201);
    }

    public function test_untrusted_request_id_is_replaced_and_responses_are_not_cached(): void
    {
        TrasladosTestIdentity::como('medico');
        foreach (['<script>alert(1)</script>', str_repeat('a', 65), "req\r\nX-Inyectada: 1"] as $cabecera) {
            $id = $this->withHeaders(['X-Request-ID' => $cabecera])->getJson('/api/v1/lab-tests')
                ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('meta.request_id');
            $this->assertMatchesRegularExpression('/^req-[0-9a-f]{16}$/', $id);
        }
    }
}
