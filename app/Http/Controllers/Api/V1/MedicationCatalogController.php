<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\MedicationCatalog\AuditedCatalogWrites;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\MedicationCatalog\Application\MedicationLookup;
use MicroHis\MedicationCatalog\Application\MedicationNotFound;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Presentation\CatalogResponse;
use MicroHis\MedicationCatalog\Presentation\Request as CatalogRequest;
use MicroHis\MedicationCatalog\Presentation\Router as CatalogRouter;

/**
 * API v1 del catálogo (contrato de Semana 5). Delgado: la autenticación, el tenant y el permiso los aplican los
 * middlewares de la ruta; la forma del JSON y las reglas, el micro-HIS. Aquí solo se traduce HTTP y se agrega trace_id.
 */
final class MedicationCatalogController extends Controller
{
    public function __construct(private readonly CatalogRouter $catalog, private readonly MedicationLookup $lookup,
        private readonly AuditedCatalogWrites $writes)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->forward($request, 'GET', '/medications');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->json($request, $this->writes->run('medicamentos.registrado', $this->actor($request),
            fn (): array => $this->dispatch($request, 'POST', '/medications')));
    }

    public function changeValidity(Request $request, string $medication): JsonResponse
    {
        return $this->json($request, $this->writes->run('medicamentos.vigencia_cambiada', $this->actor($request),
            fn (): array => $this->dispatch($request, 'PATCH', '/medications/' . rawurlencode($medication) . '/validity')));
    }

    public function show(Request $request, string $medication): JsonResponse
    {
        try {
            $found = $this->lookup->findById($medication) ?? throw new MedicationNotFound('Medicamento no encontrado.');
            $response = ['status' => 200, 'body' => ['medication' => CatalogResponse::medication($found)]];
        } catch (MedicationNotFound|PersistenceFailure $error) {
            $response = CatalogResponse::failure($error);
        }

        return $this->json($request, $response);
    }

    private function forward(Request $request, string $method, string $path): JsonResponse
    {
        return $this->json($request, $this->dispatch($request, $method, $path));
    }

    /** @return array{status: int, body: array} */
    private function dispatch(Request $request, string $method, string $path): array
    {
        return $this->catalog->dispatch(new CatalogRequest($method, $path, $request->query(), (string) $request->getContent()));
    }

    /** Actor y hospital salen de los middlewares (servidor), nunca del cuerpo; el trace_id es el de la respuesta. */
    private function actor(Request $request): array
    {
        $tenant = $request->attributes->get('tenant');

        return ['user_id' => $request->user('api')?->getAuthIdentifier(), 'trace_id' => $this->traceId($request),
            'hospital_id' => is_object($tenant) && method_exists($tenant, 'getKey') ? $tenant->getKey() : null];
    }

    private function traceId(Request $request): string
    {
        if (!$request->attributes->has('catalog_trace_id')) {
            $requested = (string) $request->header('X-Request-Id', '');
            $request->attributes->set('catalog_trace_id', preg_match('/^[A-Za-z0-9._-]{8,64}$/', $requested) === 1 ? $requested : 'trace-' . bin2hex(random_bytes(6)));
        }

        return $request->attributes->get('catalog_trace_id');
    }

    /** @param array{status: int, body: array} $response */
    private function json(Request $request, array $response): JsonResponse
    {
        $body = $response['body'];
        if (isset($body['medication'])) {
            $body['medication'] = self::contractFields($body['medication']);
        }
        if (isset($body['results'])) {
            $body['results'] = array_map(self::contractFields(...), $body['results']);
        }
        $body['trace_id'] = $this->traceId($request);

        return response()->json($body, $response['status'])->header('Cache-Control', 'no-store, private');
    }

    /** El contrato API v1 nombra el identificador medication_id; el micro-HIS lo serializa como id. */
    private static function contractFields(array $medication): array
    {
        return ['medication_id' => $medication['id']] + array_diff_key($medication, ['id' => true]);
    }
}
