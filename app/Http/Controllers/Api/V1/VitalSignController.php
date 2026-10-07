<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDiagnosisRequest;
use App\Http\Requests\Api\V1\StoreVitalSignRequest;
use App\Models\VitalSign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VitalSignController extends Controller
{
    private const SORTABLE = ["measured_at", "created_at"];

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int)$request->integer("per_page", 15), 50));
        $sortBy = $request->query("sort_by", "measured_at");
        $sortBy = in_array($sortBy, self::SORTABLE, true) ? $sortBy : "measured_at";
        $sortDir = $request->query("sort_dir") === "desc" ? "desc" : "asc";

        $vs = VitalSign::query()
            ->when($request->filled("medical_record_id"), fn($q) => $q->where("medical_record_id", $request->integer("medical_record_id")))
            ->when($request->filled("admission_id"), fn($q) => $q->where("admission_id", $request->integer("admission_id")))
            ->when($request->filled("from"), fn($q) => $q->whereDate("measured_at", ">=", $request->date("from")))
            ->when($request->filled("to"), fn($q) => $q->whereDate("measured_at", "<=", $request->date("to")))
            ->orderBy($sortBy, $sortDir)
            ->orderBy("id")
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($vs);
    }

    public function store(StoreVitalSignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data["tenant_id"] = $request->attributes->get("tenant")->id;
        $data["registered_by"] = Auth::id();
        $this->computeAlerts($data);
        $vs = VitalSign::query()->create($data);
        return response()->json(["vital_sign" => $vs], 201);
    }

    public function indexByMedicalRecord(Request $request, int $medicalRecordId): JsonResponse
    {
        $perPage = max(1, min((int)$request->integer("per_page", 15), 50));
        $sortBy = $request->query("sort_by", "measured_at");
        $sortBy = in_array($sortBy, self::SORTABLE, true) ? $sortBy : "measured_at";
        $sortDir = $request->query("sort_dir") === "desc" ? "desc" : "asc";

        $vs = VitalSign::query()
            ->where("medical_record_id", $medicalRecordId)
            ->when($request->filled("from"), fn($q) => $q->whereDate("measured_at", ">=", $request->date("from")))
            ->when($request->filled("to"), fn($q) => $q->whereDate("measured_at", "<=", $request->date("to")))
            ->orderBy($sortBy, $sortDir)
            ->orderBy("id")
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($vs);
    }

    private function computeAlerts(array &$data): void
    {
        $alerts = [];
        if (isset($data["temperature"]) && $data["temperature"] !== null && ($data["temperature"] < 36 || $data["temperature"] > 38)) { $alerts[] = ["field"=>"temperature","value"=>$data["temperature"]]; }
        if (isset($data["heart_rate"]) && $data["heart_rate"] !== null && ($data["heart_rate"] < 60 || $data["heart_rate"] > 100)) { $alerts[] = ["field"=>"heart_rate","value"=>$data["heart_rate"]]; }
        if (isset($data["respiratory_rate"]) && $data["respiratory_rate"] !== null && ($data["respiratory_rate"] < 12 || $data["respiratory_rate"] > 20)) { $alerts[] = ["field"=>"respiratory_rate","value"=>$data["respiratory_rate"]]; }
        if (isset($data["systolic_bp"]) && $data["systolic_bp"] !== null && ($data["systolic_bp"] < 90 || $data["systolic_bp"] > 140)) { $alerts[] = ["field"=>"systolic_bp","value"=>$data["systolic_bp"]]; }
        if (isset($data["diastolic_bp"]) && $data["diastolic_bp"] !== null && ($data["diastolic_bp"] < 60 || $data["diastolic_bp"] > 90)) { $alerts[] = ["field"=>"diastolic_bp","value"=>$data["diastolic_bp"]]; }
        if (isset($data["oxygen_saturation"]) && $data["oxygen_saturation"] !== null && $data["oxygen_saturation"] < 94) { $alerts[] = ["field"=>"oxygen_saturation","value"=>$data["oxygen_saturation"]]; }
        if (isset($data["glucose"]) && $data["glucose"] !== null && ($data["glucose"] < 70 || $data["glucose"] > 180)) { $alerts[] = ["field"=>"glucose","value"=>$data["glucose"]]; }
        $data["has_alert"] = !empty($alerts);
        $data["alert_details"] = empty($alerts) ? null : json_encode($alerts);
    }
}
