<?php

declare(strict_types=1);

namespace Tests\Feature\TrasladosAltas;

use App\TrasladosAltas\ResolutorActorLocal;
use DateTimeImmutable;
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
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TrasladosTestIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity());
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity());
        $this->hospital = new InMemoryRegistroInternacion();
        $this->hospital->episodios = ['00000000-0000-4000-8000-0000000000c1' => ['tipo' => 'inpatient', 'estado' => 'open', 'inicio' => new DateTimeImmutable('-2 days'), 'fin' => null]];
        $this->hospital->estancias = [self::ESTANCIA => ['episodio' => '00000000-0000-4000-8000-0000000000c1', 'cama' => self::CAMA_A, 'inicio' => new DateTimeImmutable('-1 day'), 'fin' => null]];
        $this->hospital->camas = [self::CAMA_A => 'available', self::CAMA_B => 'available'];
        $this->app->instance(RegistroInternacion::class, $this->hospital);
        $this->app->instance(Autorizador::class, new class implements Autorizador {
            public function permite(string $actorRefId, Operacion $operacion): bool
            {
                return true;
            }
        });
        $this->app->instance(ResolutorActorLocal::class, new class implements ResolutorActorLocal {
            public function referencia(string $centralUserId): ?string
            {
                return $centralUserId === '00000000-0000-4000-8000-00000000c0a1' ? '00000000-0000-4000-8000-0000000000f1' : null;
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    /** Activación SIMULADA solo dentro de la prueba: la configuración compartida los mantiene en pending. */
    private function activarPropuesta(): void
    {
        config()->set('rbac.roles.enfermeria', [...config('rbac.roles.enfermeria'), 'traslados.registrar', 'traslados.ver']);
        config()->set('rbac.roles.medico', [...config('rbac.roles.medico'), 'altas.registrar', 'traslados.ver']);
    }

    public function test_identity_tenant_and_pending_permissions_are_enforced(): void
    {
        $traslado = ['estancia_esperada_id' => self::ESTANCIA, 'cama_destino_id' => self::CAMA_B, 'motivo' => 'Aislamiento'];
        TrasladosTestIdentity::como(null);
        $this->postJson('/api/v1/traslados', $traslado)->assertStatus(401);
        TrasladosTestIdentity::como('enfermeria');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->postJson('/api/v1/traslados', $traslado)->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
        // Con la configuración real, los permisos están en pending: nadie puede operar todavía.
        foreach (['enfermeria', 'medico', 'superadmin'] as $rol) {
            TrasladosTestIdentity::como($rol);
            $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])->postJson('/api/v1/traslados', $traslado)->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        }
        $this->assertNull($this->hospital->estancias[self::ESTANCIA]['fin']);
    }

    public function test_transfer_consult_and_discharge_with_the_proposed_roles(): void
    {
        $this->activarPropuesta();
        TrasladosTestIdentity::como('enfermeria');
        $id = $this->withHeaders(['X-Request-Id' => 'req-traslado-01'])->postJson('/api/v1/traslados', ['estancia_esperada_id' => self::ESTANCIA,
            'cama_destino_id' => self::CAMA_B, 'motivo' => 'Aislamiento'])->assertStatus(201)->assertJsonPath('trace_id', 'req-traslado-01')
            ->assertHeader('Cache-Control', 'no-store, private')->json('estancia_destino_id');
        $this->assertSame('00000000-0000-4000-8000-0000000000f1', $this->hospital->estancias[$id]['actor']);
        $this->getJson('/api/v1/estancias/' . self::ESTANCIA . '/internacion')->assertOk()->assertJsonPath('estancia_consultada_abierta', false);
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $id])->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('medico');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $id])->assertOk()->assertJsonPath('resultado', 'exito');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $id])->assertStatus(409)->assertJsonPath('motivo', 'EPISODIO_NO_ABIERTO');
    }

    public function test_actor_without_local_reference_and_unavailable_hospital(): void
    {
        $this->activarPropuesta();
        TrasladosTestIdentity::como('medico', '00000000-0000-4000-8000-00000000c0ff');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => self::ESTANCIA])->assertStatus(403)->assertJsonPath('codigo', 'ACTOR_SIN_REFERENCIA_LOCAL');
        TrasladosTestIdentity::como('medico');
        $this->app->bind(RegistroInternacion::class, static fn () => throw new PDOException('sin conexión'));
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => self::ESTANCIA])->assertStatus(503)
            ->assertJsonPath('codigo', 'HOSPITAL_NO_DISPONIBLE')->assertHeader('Retry-After', '5');
        $this->assertNull($this->hospital->estancias[self::ESTANCIA]['fin']);
    }
}
