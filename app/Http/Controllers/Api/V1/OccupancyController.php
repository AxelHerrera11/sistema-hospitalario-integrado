<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MicroHis\Occupancy\Application\OccupancySummaryQuery;
use MicroHis\Occupancy\Application\PersistenceFailure;

/**
 * Fotografía de ocupación hospitalaria (ASII-09). Solo lectura: no asigna,
 * traslada ni libera camas. La autorización la resuelven los middleware de
 * tenant, JWT y RBAC. El log es estructurado y no contiene datos de pacientes.
 */
final class OccupancyController extends Controller
{
    private const CUTOFF_FORMAT = 'Y-m-d\TH:i:sP';

    public function __construct(private readonly OccupancySummaryQuery $query)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $requestId = $this->requestId($request);
        $cutoff = $this->cutoff($request);

        if ($cutoff === null) {
            return $this->error(
                'INVALID_CUTOFF',
                'El corte debe ser una fecha ISO-8601 con zona horaria.',
                400,
                $requestId,
            );
        }

        $startedAt = microtime(true);

        try {
            $snapshot = $this->query->execute($cutoff);
        } catch (PersistenceFailure) {
            Log::warning('occupancy.unavailable', [
                'request_id' => $requestId,
                'hospital_id' => $this->hospitalId($request),
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->error(
                'OCCUPANCY_UNAVAILABLE',
                'La base de ocupacion no esta disponible.',
                503,
                $requestId,
            );
        }

        Log::info('occupancy.snapshot', [
            'request_id' => $requestId,
            'hospital_id' => $this->hospitalId($request),
            'cutoff_at' => $cutoff->format(DATE_ATOM),
            'inconsistencies' => $snapshot['global']['inconsistencies'] ?? null,
            'duration_ms' => $this->durationMs($startedAt),
        ]);

        return response()->json([
            'data' => $snapshot,
            'meta' => ['request_id' => $requestId],
        ])->header('Cache-Control', 'no-store, private');
    }

    private function cutoff(Request $request): ?\DateTimeImmutable
    {
        $value = $request->query('cutoff_at');

        if ($value === null || $value === '') {
            return new \DateTimeImmutable();
        }

        if (! is_string($value)) {
            return null;
        }

        $cutoff = \DateTimeImmutable::createFromFormat(self::CUTOFF_FORMAT, $value);

        if ($cutoff === false) {
            return null;
        }

        $errors = \DateTimeImmutable::getLastErrors();

        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $cutoff;
    }

    private function requestId(Request $request): string
    {
        $header = $request->header('X-Request-ID');

        if (is_string($header) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $header) === 1) {
            return $header;
        }

        return 'req-' . bin2hex(random_bytes(8));
    }

    private function hospitalId(Request $request): ?string
    {
        $tenant = $request->attributes->get('tenant');

        if (is_object($tenant) && method_exists($tenant, 'getKey')) {
            $key = $tenant->getKey();

            return is_string($key) ? $key : null;
        }

        return null;
    }

    private function durationMs(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 3);
    }

    private function error(string $code, string $message, int $status, string $requestId): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message],
            'meta' => ['request_id' => $requestId],
        ], $status)->header('Cache-Control', 'no-store, private');
    }
}
