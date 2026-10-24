<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\Reportes\Application\CalculateCancellationRate;
use MicroHis\Reportes\Domain\IndicatorViolation;
use RuntimeException;

/**
 * Endpoint del indicador IND-RA-01 (ASII-23, semanas 5 y 16). Solo lectura:
 * la autorización la resuelven los middleware de tenant, JWT y RBAC; el caso
 * de uso del Micro-HIS resuelve regla y estados; aquí solo se traduce HTTP.
 */
final class ReportesIndicatorController extends Controller
{
    public function __construct(private readonly CalculateCancellationRate $useCase)
    {
    }

    public function calcular(Request $request): JsonResponse
    {
        $payload = $request->only(['from', 'until', 'service_code']);

        foreach (['from', 'until'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key])) {
                return $this->error('entrada_invalida', 'Se espera la cadena requerida.', 422);
            }
        }

        $service = $payload['service_code'] ?? '';
        if ($service !== '' && ! is_string($service)) {
            return $this->error('entrada_invalida', 'El filtro de sala debe ser texto.', 422);
        }

        try {
            $reading = $this->useCase->execute([
                'from' => $payload['from'],
                'until' => $payload['until'],
                'service_code' => is_string($service) ? $service : '',
            ]);
        } catch (IndicatorViolation $violation) {
            return $this->error($violation->getMessage(), 'La peticion viola la regla del periodo.', 422);
        } catch (RuntimeException $persistence) {
            return $this->error('REPORTES_UNAVAILABLE', 'La base de episodios no esta disponible.', 503);
        }

        return response()->json([
            'data' => [
                'indicator' => $reading->indicator,
                'formula_version' => $reading->formulaVersion,
                'percentage' => $reading->percentage,
                'state' => $reading->state,
                'computed_at' => $reading->computedAt,
            ],
            'meta' => ['filters' => $service !== '' ? ['service_code' => $service] : null],
        ])->header('Cache-Control', 'no-store, private');
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
