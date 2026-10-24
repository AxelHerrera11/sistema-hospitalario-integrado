<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MicroHis\ReportesOperativos\Application\ReportExporter;
use MicroHis\ReportesOperativos\Application\ReportSource;
use MicroHis\ReportesOperativos\Domain\ReportDefinition;
use MicroHis\ReportesOperativos\Domain\ReportFailure;
use MicroHis\ReportesOperativos\Domain\ReportFilters;

/**
 * Reportes operativos exportables (ASII-27): catálogo, vista previa paginada y descarga CSV reproducible
 * (los filtros aplicados viajan en `X-Report-Filters`). Errores con el sobre común de ASII-24.
 */
final class ReportesOperativosController extends Controller
{
    /**
     * @param list<ReportDefinition> $definiciones
     * @param list<ReportExporter> $exportadores
     */
    public function __construct(
        private readonly ReportSource $fuente,
        private readonly array $definiciones,
        private readonly array $exportadores,
    ) {
    }

    public function catalogo(): JsonResponse
    {
        return $this->json(['data' => array_map(static fn (ReportDefinition $d): array => [
            'id' => $d->id(), 'nombre' => $d->name(), 'filtros' => $d->filters(), 'formatos' => ['csv'],
        ], $this->definiciones)]);
    }

    public function vistaPrevia(Request $request, string $reporte): JsonResponse|Response
    {
        return $this->conReporte($request, $reporte, function (ReportDefinition $d, ReportFilters $f, array $filas) use ($request): JsonResponse {
            $pagina = max(1, (int) $request->query('page', 1));

            return $this->json([
                'data' => array_slice($filas, ($pagina - 1) * $f->perPage, $f->perPage),
                'meta' => ['current_page' => $pagina, 'per_page' => $f->perPage, 'total' => count($filas),
                    'last_page' => max(1, (int) ceil(count($filas) / $f->perPage)), 'filters' => $f->toArray()],
            ]);
        });
    }

    public function exportar(Request $request, string $reporte): JsonResponse|Response
    {
        $formato = strtolower((string) $request->query('format', 'csv'));
        $exportador = current(array_filter($this->exportadores, static fn (ReportExporter $e): bool => $e->supports($formato)));
        if ($exportador === false) {
            return $this->error($request, 406, 'report_format_unsupported', 'El formato solicitado no está disponible; use csv.');
        }

        return $this->conReporte($request, $reporte, static function (ReportDefinition $d, ReportFilters $f, array $filas) use ($exportador): Response {
            $nombre = sprintf('%s_%s_%s.csv', $d->id(), $f->dateFrom ?? 'inicio', $f->dateTo ?? 'fin');

            return response($exportador->export($d, $filas), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$nombre}\"",
                'X-Report-Filters' => json_encode($f->toArray(), JSON_UNESCAPED_SLASHES),
                'Cache-Control' => 'no-store, private',
            ]);
        });
    }

    private function conReporte(Request $request, string $id, callable $responder): JsonResponse|Response
    {
        $definicion = current(array_filter($this->definiciones, static fn (ReportDefinition $d): bool => $d->id() === $id));
        if ($definicion === false) {
            return $this->error($request, 404, 'report_not_found', 'El reporte no existe.');
        }
        try {
            $filtros = ReportFilters::fromQuery($request->query());

            return $responder($definicion, $filtros, $definicion->rows($this->fuente, $filtros));
        } catch (ReportFailure $e) {
            return $this->error($request, $e->status, $e->errorCode, $e->getMessage());
        }
    }

    private function error(Request $request, int $status, string $code, string $mensaje): JsonResponse
    {
        $traza = (string) $request->header('X-Request-Id', '');
        $traza = preg_match('/^[A-Za-z0-9._-]{8,64}$/', $traza) === 1 ? $traza : 'trace-'.bin2hex(random_bytes(6));

        return $this->json(['message' => $mensaje, 'code' => $code, 'trace_id' => $traza], $status);
    }

    /** @param array<string, mixed> $cuerpo */
    private function json(array $cuerpo, int $status = 200): JsonResponse
    {
        return response()->json($cuerpo, $status)->header('Cache-Control', 'no-store, private');
    }
}
