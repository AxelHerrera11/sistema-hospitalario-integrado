<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MicroHis\NotasSoap\Presentation\Request as SoapRequest;
use MicroHis\NotasSoap\Presentation\Router as SoapRouter;
use PDOException;

/**
 * API v1 de Notas SOAP (contrato de Semana 5). Delgado: autenticación, tenant y permiso los aplican los middlewares de
 * la ruta; las reglas y el JSON, el micro-HIS. Aquí solo se traduce HTTP y se agrega el trace_id.
 */
final class SoapNoteController extends Controller
{
    public function __construct(private readonly SoapRouter $notes, private readonly ResolutorActorLocal $actors)
    {
    }

    public function store(Request $request, string $episode): JsonResponse
    {
        return $this->forward($request, 'POST', '/care-episodes/' . rawurlencode($episode) . '/soap-notes');
    }

    public function associateDiagnosis(Request $request, string $note): JsonResponse
    {
        return $this->forward($request, 'POST', '/soap-notes/' . rawurlencode($note) . '/diagnoses');
    }

    public function sign(Request $request, string $note): JsonResponse
    {
        return $this->forward($request, 'POST', '/soap-notes/' . rawurlencode($note) . '/sign');
    }

    public function show(Request $request, string $note): JsonResponse
    {
        return $this->forward($request, 'GET', '/soap-notes/' . rawurlencode($note));
    }

    /**
     * El actor sale de la identidad autenticada (servidor), nunca de una cabecera ni del cuerpo: el usuario CENTRAL del JWT
     * se traduce a su referencia en local_user_refs del hospital, que es la que guardan la autoría y la auditoría.
     */
    private function forward(Request $request, string $method, string $path): JsonResponse
    {
        $traceId = (string) Str::uuid();
        $body = $this->body($request);
        try {
            $actor = $this->actors->referencia((string) $request->user('api')?->getAuthIdentifier());
        } catch (PDOException|QueryException) {
            $actor = false;
        }
        $error = static fn (int $status, string $code): array => ['status' => $status, 'body' => json_encode(['code' => $code], JSON_THROW_ON_ERROR)];
        $response = match (true) {
            $actor === false => $error(503, 'HOSPITAL_NO_DISPONIBLE'),
            $actor === null => $error(403, 'ACTOR_SIN_REFERENCIA_LOCAL'), // mismo código que Traslados y Expediente
            $body === null => $error(422, 'PAYLOAD_INVALID'),
            default => $this->notes->dispatch(new SoapRequest($method, $path, ['X-User-ID' => $actor], $body)),
        };
        $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR) + ['trace_id' => $traceId];

        return response()->json($data, $response['status'])->header('X-Trace-Id', $traceId)->header('Cache-Control', 'no-store, private');
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
