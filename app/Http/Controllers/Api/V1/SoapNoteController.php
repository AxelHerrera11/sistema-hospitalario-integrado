<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\NotasSoap\LocalActorResolver;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MicroHis\NotasSoap\Presentation\Request as SoapRequest;
use MicroHis\NotasSoap\Presentation\Router as SoapRouter;
use PDOException;

/**
 * API v1 de Notas SOAP (contrato de Semana 5). Delgado: autenticación, tenant y permiso los aplican los middlewares de
 * la ruta; las reglas y el JSON, el micro-HIS. Aquí solo se identifica al actor local, se traduce HTTP y se agrega el trace_id.
 */
final class SoapNoteController extends Controller
{
    public function store(Request $request, string $episode): JsonResponse
    {
        return $this->forward($request, 'POST', '/care-episodes/' . rawurlencode($episode) . '/soap-notes');
    }

    public function associateDiagnosis(Request $request, string $note): JsonResponse
    {
        return $this->forward($request, 'POST', '/soap-notes/' . rawurlencode($note) . '/diagnoses');
    }

    public function correct(Request $request, string $note): JsonResponse
    {
        return $this->forward($request, 'POST', '/soap-notes/' . rawurlencode($note) . '/corrections');
    }

    public function sign(Request $request, string $note): JsonResponse
    {
        return $this->forward($request, 'POST', '/soap-notes/' . rawurlencode($note) . '/sign');
    }

    public function show(Request $request, string $note): JsonResponse
    {
        return $this->forward($request, 'GET', '/soap-notes/' . rawurlencode($note));
    }

    /** El actor sale de la identidad autenticada (servidor): el sub del token se traduce a su referencia local, nunca de una cabecera ni del cuerpo. */
    private function forward(Request $request, string $method, string $path): JsonResponse
    {
        $traceId = (string) Str::uuid();
        $body = $this->body($request);
        try {
            $actor = app(LocalActorResolver::class)->reference((string) $request->user('api')?->getAuthIdentifier());
            $response = match (true) {
                $body === null => $this->error(422, 'PAYLOAD_INVALID'),
                $actor === null => $this->error(403, 'LOCAL_USER_NOT_FOUND'),
                default => app(SoapRouter::class)->dispatch(new SoapRequest($method, $path, ['X-User-ID' => $actor], $body)),
            };
        } catch (PDOException|QueryException) {
            // Sin conexión con HOSPITAL no se abrió ninguna transacción: se sabe que no hubo cambios.
            $response = $this->error(503, 'HOSPITAL_UNAVAILABLE');
        }
        $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR) + ['trace_id' => $traceId];

        return response()->json($data, $response['status'])->header('X-Trace-Id', $traceId)->header('Cache-Control', 'no-store, private');
    }

    /** @return array{status: int, body: string} */
    private function error(int $status, string $code): array
    {
        return ['status' => $status, 'body' => json_encode(['code' => $code], JSON_THROW_ON_ERROR)];
    }

    /** @return array<string, mixed>|null null si el cuerpo no es un objeto JSON */
    private function body(Request $request): ?array
    {
        if ($request->isMethod('GET') || (string) $request->getContent() === '') {
            return [];
        }
        $decoded = json_decode((string) $request->getContent(), true);

        return is_array($decoded) && !array_is_list($decoded) ? $decoded : null;
    }
}
