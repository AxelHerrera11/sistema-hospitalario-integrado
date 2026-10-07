<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateDiagnosisRequest;
use App\Models\Diagnosis;
use Illuminate\Http\JsonResponse;

class DiagnosisController extends Controller
{
    public function update(UpdateDiagnosisRequest $request, int $id): JsonResponse
    {
        $diagnosis = Diagnosis::query()->findOrFail($id);
        $diagnosis->update($request->validated());

        return response()->json(["diagnosis" => $diagnosis->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $diagnosis = Diagnosis::query()->findOrFail($id);
        $diagnosis->delete();

        return response()->json(null, 204);
    }
}
