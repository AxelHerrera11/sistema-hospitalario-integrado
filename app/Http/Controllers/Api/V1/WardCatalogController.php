<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MicroHis\SalasCamas\Application\CatalogService;
use MicroHis\SalasCamas\Application\PersistenceFailure;
use MicroHis\SalasCamas\Domain\Ward;

final class WardCatalogController extends Controller
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $requestId = $this->requestId($request);

        try {
            $wards = $this->catalog->listWards();
        } catch (PersistenceFailure) {
            Log::warning('salas_camas.wards.unavailable', ['request_id' => $requestId]);

            return response()->json([
                'error' => [
                    'code' => 'WARD_CATALOG_UNAVAILABLE',
                    'message' => 'No se pudo consultar el catálogo de salas.',
                ],
                'meta' => ['request_id' => $requestId],
            ], 503)->header('Cache-Control', 'no-store, private');
        }

        return response()->json([
            'data' => array_map(static fn (Ward $ward): array => [
                'ward_id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
                'location' => $ward->location,
                'status' => $ward->status,
            ], $wards),
            'meta' => ['count' => count($wards), 'request_id' => $requestId],
        ])->header('Cache-Control', 'no-store, private');
    }

    private function requestId(Request $request): string
    {
        $requested = $request->header('X-Request-ID');

        return is_string($requested) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $requested) === 1
            ? $requested
            : 'req-' . bin2hex(random_bytes(8));
    }
}
