<?php

declare(strict_types=1);

namespace App\ExpedienteMedico;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MicroHis\ExpedienteMedico\Application\HospitalStoresProvider;
use MicroHis\ExpedienteMedico\Integration\GobernanzaClinicalAudit;
use MicroHis\ExpedienteMedico\Integration\RbacAccessAuthorizer;
use MicroHis\ExpedienteMedico\Presentation\Request as MicroHisRequest;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Rbac\Application\Authorize;
use PDOException;

/**
 * API v1 del expediente. Delgado: tenant, jwt.auth y el permiso rbac: de cada ruta los aplican los middlewares.
 * Aquí se traduce la identidad (usuario CENTRAL del JWT → local_user_refs del hospital) y se delega en el
 * micro-HIS con los servicios REALES del contenedor: Authorize (RBAC) y RecordAuditEvent (Gobernanza).
 */
final class ExpedienteMedicoController extends Controller
{
    public function consult(Request $request, string $patientCode): Response|JsonResponse
    {
        return $this->dispatch($request, 'GET', $patientCode);
    }

    /** Apertura idempotente: 201 si se creó, 200 si ya existía (RF-EXP-01). */
    public function open(Request $request, string $patientCode): Response|JsonResponse
    {
        return $this->dispatch($request, 'POST', $patientCode);
    }

    private function dispatch(Request $request, string $method, string $patientCode): Response|JsonResponse
    {
        $central = (string) $request->user('api')?->getAuthIdentifier();
        try {
            $local = app(ResolutorActorLocal::class)->referencia($central);
        } catch (PDOException|QueryException) {
            return response()->json(['code' => 'HOSPITAL_NO_DISPONIBLE'], 503)->withHeaders(['Cache-Control' => 'no-store']);
        }
        if ($local === null) {
            // Mismo código que Traslados: el usuario autenticado no tiene referencia activa en este hospital.
            return response()->json(['code' => 'ACTOR_SIN_REFERENCIA_LOCAL'], 403)->withHeaders(['Cache-Control' => 'no-store']);
        }

        $router = expediente_router(
            new RbacAccessAuthorizer(app(Authorize::class)),
            app(HospitalStoresProvider::class),
            new GobernanzaClinicalAudit(app(RecordAuditEvent::class)),
        );
        $result = $router->dispatch(new MicroHisRequest($method, '/patients/' . rawurlencode($patientCode) . '/medical-record', [
            'X-User-ID' => $central,
            'X-Tenant-ID' => self::hospitalOf($request),
            'X-Correlation-ID' => (string) $request->header('X-Request-Id', ''),
            'X-Local-User-Ref' => $local,
        ]));

        return response($result['body'], $result['status'], $result['headers']);
    }

    /** Hospital resuelto por el middleware tenant (atributo `tenant` con getKey()), o cadena vacía. */
    public static function hospitalOf(Request $request): string
    {
        $tenant = $request->attributes->get('tenant');

        return is_object($tenant) && method_exists($tenant, 'getKey') ? (string) $tenant->getKey() : '';
    }
}
