<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MicroHis\CatalogoLab\Application\FindActiveLabTests;
use MicroHis\CatalogoLab\Application\PersistenceFailure;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Domain\DomainRuleViolation;
use PDOException;

/**
 * Catálogo versionado de pruebas de laboratorio (ASII-17). Traduce HTTP a los casos de uso del Micro-HIS; las reglas de rangos y
 * de vigencia viven en el dominio y en `lab_tests`. El autor sale del JWT, nunca del cuerpo.
 */
final class LabTestController extends Controller
{
    private const FIELDS = ['code', 'name', 'specimen_type', 'min_volume_ml', 'result_type', 'unit', 'reference_low', 'reference_high',
        'critical_low', 'critical_high', 'valid_from'];

    public function __construct(private readonly RegisterLabTest $register, private readonly FindActiveLabTests $find, private readonly ResolutorActorLocal $actors) {}

    public function index(Request $request): JsonResponse
    {
        $requestId = $this->requestId($request);
        $date = $request->query('date', now()->toDateString());
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
            return $this->error('DATE_INVALID', 'La fecha debe tener el formato AAAA-MM-DD.', 422, $requestId, 'date');
        }
        $search = $request->query('search', '');

        try {
            return $this->json(['date' => $date, 'lab_tests' => $this->find->execute($date, is_string($search) ? $search : '')], 200, $requestId);
        } catch (PersistenceFailure) {
            return $this->unavailable($request, $requestId);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = $this->requestId($request);

        try {
            $actor = $this->actors->referencia((string) $request->user()->getAuthIdentifier());
            if ($actor === null) {
                return $this->error('ACTOR_WITHOUT_LOCAL_REFERENCE', 'El usuario no tiene una referencia local vigente en este hospital.', 403, $requestId);
            }
            $test = $this->register->execute($request->only(self::FIELDS), $actor);
        } catch (DomainRuleViolation $violation) {
            $status = $violation->rule === 'LAB_TEST_VALIDITY_OVERLAP' ? 409 : 422;

            return $this->error($violation->rule, 'La prueba no cumple una regla del catálogo.', $status, $requestId, $violation->field);
        } catch (PersistenceFailure|PDOException) {
            return $this->unavailable($request, $requestId);
        }

        Log::info('catalogo_laboratorio.prueba_registrada', ['request_id' => $requestId, 'hospital_id' => $request->header('X-Tenant-ID'), 'lab_test_id' => $test->id]);

        return $this->json(['lab_test_id' => $test->id, 'code' => $test->code, 'name' => $test->name, 'specimen_type' => $test->specimenType,
            'min_volume_ml' => $test->minVolumeMl, 'result_type' => $test->resultType, 'unit' => $test->unit, 'reference_low' => $test->referenceLow,
            'reference_high' => $test->referenceHigh, 'critical_low' => $test->criticalLow, 'critical_high' => $test->criticalHigh,
            'version_no' => $test->versionNo, 'valid_from' => $test->validFrom], 201, $requestId);
    }

    private function unavailable(Request $request, string $requestId): JsonResponse
    {
        Log::warning('catalogo_laboratorio.no_disponible', ['request_id' => $requestId, 'hospital_id' => $request->header('X-Tenant-ID')]);

        return $this->error('LAB_CATALOG_UNAVAILABLE', 'El catálogo de laboratorio no está disponible.', 503, $requestId)->header('Retry-After', '5');
    }

    private function requestId(Request $request): string
    {
        $header = $request->header('X-Request-ID');

        return is_string($header) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $header) === 1 ? $header : 'req-'.bin2hex(random_bytes(8));
    }

    private function json(array $data, int $status, string $requestId): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => ['request_id' => $requestId]], $status)->header('Cache-Control', 'no-store, private');
    }

    private function error(string $code, string $message, int $status, string $requestId, ?string $field = null): JsonResponse
    {
        return response()->json(['error' => array_filter(['code' => $code, 'message' => $message, 'field' => $field]), 'meta' => ['request_id' => $requestId]], $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
