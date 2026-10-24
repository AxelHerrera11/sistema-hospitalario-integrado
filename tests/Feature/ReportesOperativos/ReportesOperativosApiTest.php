<?php

declare(strict_types=1);

namespace Tests\Feature\ReportesOperativos;

use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\ReportesOperativos\Application\ReportSource;
use MicroHis\ReportesOperativos\Domain\ReportFailure;
use MicroHis\ReportesOperativos\Domain\ReportFilters;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** API v1 de reportes operativos (ASII-27, semana 16) con RBAC real; la fuente HOSPITAL es un doble. */
final class ReportesOperativosApiTest extends TestCase
{
    /** @var list<ReportFilters> */
    private array $consultas = [];

    private bool $fuenteCaida = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', ReportesTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', ReportesTestIdentity::class);
        $this->app->instance(RoleResolver::class, new ReportesTestIdentity());
        $this->app->instance(DenialRecorder::class, new ReportesTestIdentity());
        $this->app->instance(EnsureHospitalDatabaseTenant::class, new class {
            public function handle($request, $next)
            {
                return $next($request);
            }
        });
        $prueba = $this;
        $this->app->instance(ReportSource::class, new class ($prueba) implements ReportSource {
            public function __construct(private readonly ReportesOperativosApiTest $prueba)
            {
            }

            public function citas(ReportFilters $filters): array
            {
                return $this->prueba->registrar($filters);
            }
        });
        ReportesTestIdentity::como('admin_hospital');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    /** @return list<array<string, string>> */
    public function registrar(ReportFilters $filtros): array
    {
        if ($this->fuenteCaida) {
            throw new ReportFailure(503, 'report_source_unavailable', 'La fuente del reporte no está disponible.');
        }
        $this->consultas[] = $filtros;

        return [
            ['fecha' => '2026-11-02', 'servicio' => 'Pediatria', 'estado' => 'completed', 'total' => '14'],
            ['fecha' => '2026-11-03', 'servicio' => '=Medicina', 'estado' => 'completed', 'total' => '9'],
        ];
    }

    public function test_catalogo_y_permisos(): void
    {
        $this->getJson('/api/v1/reportes')->assertOk()->assertJsonPath('data.0.id', 'citas-por-servicio');

        ReportesTestIdentity::como('enfermeria');
        $this->getJson('/api/v1/reportes')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');

        ReportesTestIdentity::como('tecnico_laboratorio'); // reportes.ver sin reportes.exportar
        $this->getJson('/api/v1/reportes/citas-por-servicio?date_from=2026-11-01')->assertOk();
        $this->get('/api/v1/reportes/citas-por-servicio/export?date_from=2026-11-01')->assertStatus(403);
    }

    public function test_vista_previa_paginada_con_los_filtros_aplicados(): void
    {
        $this->getJson('/api/v1/reportes/citas-por-servicio?date_from=2026-11-01&date_to=2026-11-30&per_page=1&page=2&status=completed')
            ->assertOk()
            ->assertJsonPath('data.0.servicio', '=Medicina')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.filters.status', 'completed');
        $this->assertSame('completed', $this->consultas[0]->extra['status']);
    }

    public function test_exporta_csv_reproducible_y_sin_formulas(): void
    {
        $respuesta = $this->get('/api/v1/reportes/citas-por-servicio/export?date_from=2026-11-01&date_to=2026-11-30');

        $respuesta->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="citas-por-servicio_2026-11-01_2026-11-30.csv"');
        $this->assertSame('2026-11-30', json_decode($respuesta->headers->get('X-Report-Filters'), true)['date_to']);
        $this->assertStringContainsString("fecha,servicio,estado,total\n2026-11-02,Pediatria,completed,14\n", $respuesta->getContent());
        $this->assertStringContainsString("'=Medicina", $respuesta->getContent());
    }

    public function test_errores_del_contrato(): void
    {
        $this->getJson('/api/v1/reportes/inexistente?date_from=2026-11-01')->assertNotFound()->assertJsonPath('code', 'report_not_found');
        $this->getJson('/api/v1/reportes/citas-por-servicio')->assertStatus(422)->assertJsonPath('code', 'report_filter_invalid');
        $this->getJson('/api/v1/reportes/citas-por-servicio?date_from=2025-01-01&date_to=2026-12-31')
            ->assertStatus(422)->assertJsonPath('code', 'report_range_too_long');
        $this->getJson('/api/v1/reportes/citas-por-servicio/export?date_from=2026-11-01&format=pdf', ['X-Request-Id' => 'req-reporte-01'])
            ->assertStatus(406)->assertJsonPath('code', 'report_format_unsupported')->assertJsonPath('trace_id', 'req-reporte-01');
        $this->assertSame([], $this->consultas, 'los filtros invalidos no llegan a la base');

        $this->fuenteCaida = true;
        $this->getJson('/api/v1/reportes/citas-por-servicio?date_from=2026-11-01')->assertStatus(503)->assertJsonPath('code', 'report_source_unavailable');
    }
}
