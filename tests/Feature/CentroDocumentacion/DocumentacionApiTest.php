<?php

declare(strict_types=1);

namespace Tests\Feature\CentroDocumentacion;

use Illuminate\Contracts\Http\Kernel;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** Centro de documentación (ASII-24, semana 16) con RBAC y config/centro_documentacion.php reales. */
final class DocumentacionApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', DocumentacionTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', DocumentacionTestIdentity::class);
        $this->app->instance(RoleResolver::class, new DocumentacionTestIdentity());
        $this->app->instance(DenialRecorder::class, new DocumentacionTestIdentity());
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    /** @return list<string> */
    private function ids(string $url = '/api/v1/documentacion'): array
    {
        return array_column($this->getJson($url)->assertOk()->json('data'), 'id');
    }

    public function test_cada_rol_ve_solo_su_catalogo(): void
    {
        DocumentacionTestIdentity::como('enfermeria');
        $this->assertSame(['guia-interfaz', 'guia-ocupacion'], $this->ids());

        DocumentacionTestIdentity::como('admin_hospital');
        $this->assertContains('openapi-v1', $this->ids());
    }

    public function test_el_rol_enviado_por_el_cliente_se_ignora(): void
    {
        DocumentacionTestIdentity::como('enfermeria');
        $this->assertNotContains('openapi-v1', $this->ids('/api/v1/documentacion?rol=admin_hospital'));
    }

    public function test_entrega_el_contenido_con_su_tipo_y_version(): void
    {
        DocumentacionTestIdentity::como('admin_hospital');
        $respuesta = $this->get('/api/v1/documentacion/openapi-v1');

        $respuesta->assertOk()
            ->assertHeader('Content-Type', 'application/yaml; charset=UTF-8')
            ->assertHeader('X-Document-Version', '0.1.0');
        $this->assertStringStartsWith('openapi: 3.0.3', $respuesta->getContent());
        $this->get('/api/v1/documentacion/contrato-citas')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    }

    public function test_documento_ajeno_o_inexistente_responde_404_igual(): void
    {
        DocumentacionTestIdentity::como('enfermeria');
        $ajeno = $this->getJson('/api/v1/documentacion/openapi-v1')->assertNotFound()->assertJsonPath('code', 'document_not_found');
        $inexistente = $this->getJson('/api/v1/documentacion/no-existe')->assertNotFound();
        $this->assertSame($ajeno->json('message'), $inexistente->json('message'));
    }

    public function test_sin_sesion_401_y_rol_sin_permiso_403(): void
    {
        DocumentacionTestIdentity::como(null);
        $this->getJson('/api/v1/documentacion')->assertStatus(401);

        DocumentacionTestIdentity::como('rol_inexistente');
        $this->getJson('/api/v1/documentacion')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_cada_entrada_del_catalogo_apunta_a_un_archivo_real(): void
    {
        foreach (config('centro_documentacion.catalogo') as $documento) {
            $this->assertFileExists(base_path($documento['ruta']), $documento['id']);
        }
    }
}
