<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreVitalSignRequest;
use App\Models\VitalSign;
use App\Services\VitalSignEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signos vitales — Área 5.
 *
 * El aislamiento por hospital lo aplica BelongsToTenant en las consultas;
 * la validación de medical_record_id/admission_id además verifica que el
 * registro pertenezca al mismo tenant (StoreVitalSignRequest).
 */
class VitalSignController extends Controller
{
    private const SORTABLE = ['measured_at', 'created_at'];

    private const SUMMARY_RELATIONS = ['registeredBy:id,name'];

    public function __construct(private readonly VitalSignEvaluator $evaluator) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));
        $sortBy = $request->query('sort_by', 'measured_at');
        $sortBy = in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'measured_at';
        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        $vs = VitalSign::query()
            ->with(self::SUMMARY_RELATIONS)
            ->when($request->filled('medical_record_id'), fn ($query) => $query->where('medical_record_id', $request->integer('medical_record_id')))
            ->when($request->filled('admission_id'), fn ($query) => $query->where('admission_id', $request->integer('admission_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('measured_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('measured_at', '<=', $request->date('to')))
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($vs);
    }

    public function store(StoreVitalSignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->attributes->get('tenant')->id;
        $data['registered_by'] = Auth::id();

        // SRP: el cálculo de alertas vive en VitalSignEvaluator (RF-VS-02).
        $evaluation = $this->evaluator->evaluate($data);
        $data['has_alert'] = $evaluation['has_alert'];
        $data['alert_details'] = $evaluation['alert_details'];

        $vs = VitalSign::query()->create($data);

        return response()->json([
            'vital_sign' => $vs,
        ], 201);
    }

    public function indexByMedicalRecord(Request $request, int $medicalRecordId): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));
        $sortBy = $request->query('sort_by', 'measured_at');
        $sortBy = in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'measured_at';
        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        $vs = VitalSign::query()
            ->with(self::SUMMARY_RELATIONS)
            ->where('medical_record_id', $medicalRecordId)
            ->when($request->filled('from'), fn ($query) => $query->whereDate('measured_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('measured_at', '<=', $request->date('to')))
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($vs);
    }
}