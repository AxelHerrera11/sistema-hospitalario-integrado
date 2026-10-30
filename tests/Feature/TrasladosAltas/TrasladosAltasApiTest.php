<?php

declare(strict_types=1);

namespace Tests\Feature\TrasladosAltas;

use App\TrasladosAltas\ResolutorActorLocal;
use DateTimeImmutable;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\Operacion;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Persistence\InMemoryRegistroInternacion;
use PDOException;
use Tests\TestCase;

/** API v1 de traslados y altas con los middlewares y la matriz RBAC reales; HOSPITAL en memoria. */
final class TrasladosAltasApiTest extends TestCase
{
    private const ESTANCIA = '00000000-0000-4000-8000-0000000000e1';

    private const CAMA_A = '00000000-0000-4000-8000-0000000000b1';

    private const CAMA_B = '00000000-0000-4000-8000-0000000000b2';

    private InMemoryRegistroInternacion $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        // Instancia on-premise de prueba: su base HOSPITAL pertenece a hospital-a.
        config(['tenants.hospital_database_tenant_id' => 'hospital-a']);
        TrasladosTestIdentity::$hospital = 'hospital-a';
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TrasladosTestIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity);
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity);
        $this->hospital = new InMemoryRegistroInternacion;
        $this->hospital->episodios = ['00000000-0000-4000-8000-0000000000c1' => ['tipo' => 'inpatient', 'estado' => 'open', 'inicio' => new DateTimeImmutable('-2 days'), 'fin' => null]];
        $this->hospital->estancias = [self::ESTANCIA => ['episodio' => '00000000-0000-4000-8000-0000000000c1', 'cama' => self::CAMA_A, 'inicio' => new DateTimeImmutable('-1 day'), 'fin' => null]];
        $this->hospital->camas = [self::CAMA_A => 'available', self::CAMA_B => 'available'];
        $this->app->instance(RegistroInternacion::class, $this->hospital);
        $this->app->instance(Autorizador::class, new class implements Autorizador
        {
            public function permite(string $actorRefId, Operacion $operacion): bool
            {
                return true;
            }
        });
        $this->app->instance(ResolutorActorLocal::class, new class implements ResolutorActorLocal
        {
            public function referencia(string $centralUserId): ?string
            {
                return $centralUserId === '00000000-0000-4000-8000-00000000c0a1' ? '00000000-0000-4000-8000-0000000000f1' : null;
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }


    public function test_identity_tenant_and_active_permissions_are_enforced(): void
    {
        $traslado = ['estancia_esperada_id' => self::ESTANCIA, 'cama_destino_id' => self::CAMA_B, 'motivo' => 'Aislamiento'];
        TrasladosTestIdentity::como(null);
        $this->postJson('/api/v1/traslados', $traslado)->assertStatus(401);
        TrasladosTestIdentity::como('enfermeria');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->postJson('/api/v1/traslados', $traslado)->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
        // Matriz activa: solo enfermería traslada; el resto de los roles, incluido superadmin, queda fuera.
        foreach (['medico', 'superadmin', 'admin_hospital', 'recepcion'] as $rol) {
            TrasladosTestIdentity::como($rol);
            $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])->postJson('/api/v1/traslados', $traslado)->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        }
        $this->assertNull($this->hospital->estancias[self::ESTANCIA]['fin']);
    }

    public function test_transfer_consult_and_discharge_with_the_proposed_roles(): void
    {
        TrasladosTestIdentity::como('enfermeria');
        $id = $this->withHeaders(['X-Request-Id' => 'req-traslado-01'])->postJson('/api/v1/traslados', ['estancia_esperada_id' => self::ESTANCIA,
            'cama_destino_id' => self::CAMA_B, 'motivo' => 'Aislamiento'])->assertStatus(201)->assertJsonPath('trace_id', 'req-traslado-01')
            ->assertHeader('Cache-Control', 'no-store, private')->json('estancia_destino_id');
        $this->assertSame('00000000-0000-4000-8000-0000000000f1', $this->hospital->estancias[$id]['actor']);
        $this->getJson('/api/v1/estancias/'.self::ESTANCIA.'/internacion')->assertOk()->assertJsonPath('estancia_consultada_abierta', false);
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $id])->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('medico');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $id])->assertOk()->assertJsonPath('resultado', 'exito');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $id])->assertStatus(409)->assertJsonPath('motivo', 'EPISODIO_NO_ABIERTO');
    }

    public function test_member_of_another_hospital_cannot_read_or_change_this_hospital_stays(): void
    {
        $traslado = ['estancia_esperada_id' => self::ESTANCIA, 'cama_destino_id' => self::CAMA_B, 'motivo' => 'Aislamiento'];
        $antes = serialize([$this->hospital->estancias, $this->hospital->episodios, $this->hospital->camas]);
        $mismatch = 'HOSPITAL_DATABASE_TENANT_MISMATCH';

        // Membresía válida en hospital-b, instancia con la base de hospital-a.
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b']);
        TrasladosTestIdentity::$hospital = 'hospital-b';
        TrasladosTestIdentity::como('enfermeria');
        $this->getJson('/api/v1/estancias/'.self::ESTANCIA.'/internacion')->assertForbidden()->assertJsonPath('error', $mismatch);
        $this->postJson('/api/v1/traslados', $traslado)->assertForbidden()->assertJsonPath('error', $mismatch);
        TrasladosTestIdentity::como('medico');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => self::ESTANCIA])->assertForbidden()->assertJsonPath('error', $mismatch);
        $this->assertSame($antes, serialize([$this->hospital->estancias, $this->hospital->episodios, $this->hospital->camas]));

        // El mismo rol en el hospital dueño de la base conserva el acceso.
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
        TrasladosTestIdentity::$hospital = 'hospital-a';
        TrasladosTestIdentity::como('enfermeria');
        $this->getJson('/api/v1/estancias/'.self::ESTANCIA.'/internacion')->assertOk();
        $this->postJson('/api/v1/traslados', $traslado)->assertStatus(201);

        // Sin hospital asignado a la base se cierra el paso.
        config(['tenants.hospital_database_tenant_id' => null]);
        $this->getJson('/api/v1/estancias/'.self::ESTANCIA.'/internacion')->assertStatus(503)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
    }

    public function test_invalid_request_does_not_expose_console_syntax(): void
    {
        TrasladosTestIdentity::como('enfermeria');
        $this->postJson('/api/v1/traslados', ['estancia_esperada_id' => self::ESTANCIA, 'cama_destino_id' => self::CAMA_B, 'motivo' => ''])
            ->assertStatus(422)->assertJsonPath('campo', 'motivo')->assertJsonMissingPath('uso');
    }

    public function test_actor_without_local_reference_and_unavailable_hospital(): void
    {
        TrasladosTestIdentity::como('medico', '00000000-0000-4000-8000-00000000c0ff');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => self::ESTANCIA])->assertStatus(403)->assertJsonPath('codigo', 'ACTOR_SIN_REFERENCIA_LOCAL');
        TrasladosTestIdentity::como('medico');
        $this->app->bind(RegistroInternacion::class, static fn () => throw new PDOException('sin conexión'));
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => self::ESTANCIA])->assertStatus(503)
            ->assertJsonPath('codigo', 'HOSPITAL_NO_DISPONIBLE')->assertHeader('Retry-After', '5');
        $this->assertNull($this->hospital->estancias[self::ESTANCIA]['fin']);
    }

    public function test_body_cannot_spoof_actor_or_tenant_and_wrong_role_cannot_write(): void
    {
        $campos = ['estancia_esperada_id' => self::ESTANCIA, 'cama_destino_id' => self::CAMA_B, 'motivo' => 'Aislamiento',
            'actor_ref_id' => '00000000-0000-4000-8000-000000000099', 'hospital_id' => 'hospital-b'];
        TrasladosTestIdentity::como('recepcion');
        $this->postJson('/api/v1/traslados', $campos)->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->assertNull($this->hospital->estancias[self::ESTANCIA]['fin']);

        TrasladosTestIdentity::como('enfermeria');
        $destino = $this->postJson('/api/v1/traslados', $campos)->assertStatus(201)->json('estancia_destino_id');
        $this->assertSame('00000000-0000-4000-8000-0000000000f1', $this->hospital->estancias[$destino]['actor']);
    }

    public function test_database_error_hides_details_and_replaces_untrusted_trace(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('medico');
        $this->app->bind(RegistroInternacion::class, static fn () => throw new PDOException('SQLSTATE[28P01] password=secreto'));

        $respuesta = $this->withHeaders(['X-Request-Id' => '../../traza-no-confiable'])->postJson('/api/v1/altas', [
            'estancia_esperada_id' => self::ESTANCIA,
        ])->assertStatus(503)->assertJsonPath('codigo', 'HOSPITAL_NO_DISPONIBLE')
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Retry-After', '5');
        $this->assertMatchesRegularExpression('/^trace-[0-9a-f]{12}$/', $respuesta->json('trace_id'));
        $this->assertStringNotContainsString('SQLSTATE', $respuesta->getContent());
        $this->assertStringNotContainsString('secreto', $respuesta->getContent());
    }
}
