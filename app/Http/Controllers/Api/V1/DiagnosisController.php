<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateDiagnosisRequest;
use App\Models\Diagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DiagnosisController extends Controller
{
    public function update(UpdateDiagnosisRequest $request, int $id): JsonResponse
    {
        $diagnosis = $this->editableDiagnosis($id);
        $diagnosis->update($request->validated());

        return response()->json(["diagnosis" => $diagnosis->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $diagnosis = $this->editableDiagnosis($id);
        $diagnosis->delete();

        return response()->json(null, 204);
    }

    /**
     * RF-SOAP-03: un diagnóstico de una nota firmada es inmodificable, igual
     * que la nota. Si está firmada se responde 422 con el formato de negocio
     * del contrato (§4).
     */
    private function editableDiagnosis(int $id): Diagnosis
    {
        $diagnosis = Diagnosis::query()->with('soapNote')->findOrFail($id);

        if ($diagnosis->soapNote !== null && $diagnosis->soapNote->signed_at !== null) {
            throw ValidationException::withMessages([
                'status' => ['La nota SOAP ya está firmada y no admite modificaciones.'],
            ]);
        }

        return $diagnosis;
    }
}
