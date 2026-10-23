<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MicroHis\SalasCamas\Application\{CatalogOperationFailure, CatalogService, PersistenceFailure};
use MicroHis\SalasCamas\Domain\{Bed, Ward};

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

    public function beds(Request $request, string $ward): JsonResponse
    {
        $requestId = $this->requestId($request);

        if (!Str::isUuid($ward)) {
            return $this->wardNotFound($requestId);
        }

        try {
            $beds = $this->catalog->listBedsForWard($ward);
        } catch (CatalogOperationFailure $failure) {
            if ($failure->errorCode === 'WARD_NOT_FOUND') {
                return $this->wardNotFound($requestId);
            }

            throw $failure;
        } catch (PersistenceFailure) {
            Log::warning('salas_camas.beds.unavailable', [
                'request_id' => $requestId,
                'ward_id' => $ward,
            ]);

            return response()->json([
                'error' => [
                    'code' => 'BED_CATALOG_UNAVAILABLE',
                    'message' => 'No se pudo consultar el catálogo de camas.',
                ],
                'meta' => ['request_id' => $requestId],
            ], 503)->header('Cache-Control', 'no-store, private');
        }

        return response()->json([
            'data' => array_map(static fn (Bed $bed): array => [
                'bed_id' => $bed->id,
                'ward_id' => $bed->wardId,
                'code' => $bed->code,
                'type' => $bed->type,
                'status' => $bed->status,
            ], $beds),
            'meta' => [
                'ward_id' => $ward,
                'count' => count($beds),
                'request_id' => $requestId,
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    private function wardNotFound(string $requestId): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'WARD_NOT_FOUND',
                'message' => 'La ward solicitada no existe.',
            ],
            'meta' => ['request_id' => $requestId],
        ], 404)->header('Cache-Control', 'no-store, private');
    }

    private function requestId(Request $request): string
    {
        $requested = $request->header('X-Request-ID');

        return is_string($requested) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $requested) === 1
            ? $requested
            : 'req-' . bin2hex(random_bytes(8));
    }
}
