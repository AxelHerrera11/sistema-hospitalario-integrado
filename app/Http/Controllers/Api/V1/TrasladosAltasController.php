<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Presentation\HttpTraslados;
use PDOException;

/**
 * API v1 de traslados y altas. Delgado: tenant, jwt.auth y el permiso los aplican los middlewares de la ruta; la
 * operación, el micro-HIS (HttpTraslados). Aquí solo se identifica al actor local y se agrega trace_id.
 */
final class TrasladosAltasController extends Controller
{
    public function trasladar(Request $request): JsonResponse
    {
        return $this->operar($request, 'POST', '/traslados');
    }

    public function darAlta(Request $request): JsonResponse
    {
        return $this->operar($request, 'POST', '/altas');
    }

    public function consultar(Request $request, string $estancia): JsonResponse
    {
        return $this->operar($request, 'GET', '/estancias/' . rawurlencode($estancia) . '/internacion');
    }

    private function operar(Request $request, string $metodo, string $ruta): JsonResponse
    {
        try {
            $actor = app(ResolutorActorLocal::class)->referencia((string) $request->user('api')?->getAuthIdentifier());
            $respuesta = $actor === null
                ? HttpTraslados::conCabeceras(['estado' => 403, 'cuerpo' => ['resultado' => 'error', 'codigo' => 'ACTOR_SIN_REFERENCIA_LOCAL']])
                : (new HttpTraslados(static fn (): array => [app(RegistroInternacion::class), app(Autorizador::class)]))
                    ->atender($metodo, $ruta, $actor, (string) $request->getContent());
        } catch (PDOException|QueryException) {
            // Sin conexión con HOSPITAL no se abrió ninguna transacción: se sabe que no hubo cambios.
            $respuesta = HttpTraslados::conCabeceras(['estado' => 503, 'cuerpo' => ['resultado' => 'error', 'codigo' => 'HOSPITAL_NO_DISPONIBLE']]);
        }
        $requested = (string) $request->header('X-Request-Id', '');
        $cuerpo = $respuesta['cuerpo'] + ['trace_id' => preg_match('/^[A-Za-z0-9._-]{8,64}$/', $requested) === 1 ? $requested : 'trace-' . bin2hex(random_bytes(6))];

        return response()->json($cuerpo, $respuesta['estado'])->withHeaders($respuesta['cabeceras'] + ['Cache-Control' => 'no-store, private']);
    }
}
