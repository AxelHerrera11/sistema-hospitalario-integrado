<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MicroHis\CentroDocumentacion\Application\ConsultarCatalogo;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Centro de documentación por rol (ASII-24). Los roles se resuelven en el servidor con el usuario del JWT
 * y el hospital del tenant; cualquier `rol` enviado por el cliente se ignora (corrige RTF-01, semana 13).
 */
final class DocumentacionController extends Controller
{
    public function __construct(
        private readonly ConsultarCatalogo $catalogo,
        private readonly RoleResolver $roles,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->catalogo->visibles($this->rolesDeSesion($request))])
            ->header('Cache-Control', 'no-store, private');
    }

    public function contenido(Request $request, string $documento): JsonResponse|Response
    {
        $doc = $this->catalogo->documento($documento, $this->rolesDeSesion($request));
        $archivo = $doc === null ? false : realpath(base_path($doc->ruta));
        if ($archivo === false || !str_starts_with($archivo, realpath(base_path('docs')).DIRECTORY_SEPARATOR)) {
            return response()->json([
                'message' => 'El documento no existe o no esta disponible para su rol.',
                'code' => 'document_not_found',
                'trace_id' => preg_match('/^[A-Za-z0-9._-]{8,64}$/', $traza = (string) $request->header('X-Request-Id', '')) === 1 ? $traza : 'trace-'.bin2hex(random_bytes(6)),
            ], 404)->header('Cache-Control', 'no-store, private');
        }
        $tipo = str_ends_with($archivo, '.yaml') ? 'application/yaml' : 'text/markdown';

        return response((string) file_get_contents($archivo), 200, [
            'Content-Type' => "{$tipo}; charset=UTF-8",
            'X-Document-Version' => $doc->version,
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /** @return list<string> */
    private function rolesDeSesion(Request $request): array
    {
        $tenant = $request->attributes->get('tenant');

        return $this->roles->rolesIn((string) $request->user('api')?->getAuthIdentifier(), (string) $tenant?->getKey());
    }
}
