<?php

declare(strict_types=1);

namespace Tests\Feature\CentroDocumentacion;

use Illuminate\Contracts\Http\Kernel;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** Seguridad del centro de documentacion (ASII-24, semana 17): permisos, tenant y datos internos. */
final class DocumentacionSeguridadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', DocumentacionTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', DocumentacionTestIdentity::class);
        $this->app->instance(RoleResolver::class, new DocumentacionTestIdentity());
        $this->app->instance(DenialRecorder::class, new DocumentacionTestIdentity());
        DocumentacionTestIdentity::como('enfermeria');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_matriz_de_permisos_por_rol(): void
    {
        foreach (['superadmin', 'admin_hospital', 'medico', 'enfermeria', 'recepcion', 'tecnico_laboratorio', 'bioquimico', 'jefatura_medica'] as $rol) {
            DocumentacionTestIdentity::como($rol);
            $this->getJson('/api/v1/documentacion')->assertOk();
        }

        DocumentacionTestIdentity::como('rol_inexistente');
        $this->getJson('/api/v1/documentacion')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_aislamiento_entre_hospitales(): void
    {
        DocumentacionTestIdentity::como('enfermeria');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])
            ->getJson('/api/v1/documentacion')
            ->assertStatus(403)
            ->assertJsonPath('code', 'TENANT_MISMATCH');

        $this->app->instance(RoleResolver::class, new class implements RoleResolver {
            public function rolesIn(string $userId, string $hospitalId): array
            {
                return [];
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])
            ->getJson('/api/v1/documentacion')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_no_expone_rutas_ni_documentos_ajenos(): void
    {
        DocumentacionTestIdentity::como('enfermeria');
        $respuesta = $this->getJson('/api/v1/documentacion')->assertOk();
        $respuesta->assertHeader('Cache-Control', 'no-store, private');
        foreach ($respuesta->json('data') as $documento) {
            $this->assertSame([], array_diff(array_keys($documento), ['id', 'titulo', 'tipo', 'version']));
        }
        $cuerpo = $respuesta->getContent();
        $this->assertStringNotContainsString('ruta', $cuerpo);
        $this->assertStringNotContainsString('docs/', $cuerpo);

        $ajeno = $this->getJson('/api/v1/documentacion/openapi-v1')->assertNotFound();
        $ajeno->assertJsonPath('code', 'document_not_found')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringNotContainsString('openapi.yaml', $ajeno->getContent());
    }

    public function test_sin_sesion_responde_401(): void
    {
        DocumentacionTestIdentity::como(null);
        $this->getJson('/api/v1/documentacion')->assertStatus(401)->assertJsonPath('code', 'AUTH_REQUIRED');
    }
}
