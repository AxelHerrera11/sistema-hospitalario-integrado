<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDiagnosisRequest;
use App\Http\Requests\Api\V1\StoreSoapNoteRequest;
use App\Http\Requests\Api\V1\UpdateSoapNoteRequest;
use App\Models\Diagnosis;
use App\Models\SoapNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notas SOAP — Área 5.
 *
 * Toda ruta exige ['tenant', 'auth.jwt'] y un permiso (ver routes/api.php).
 * El aislamiento por hospital lo aplica el global scope del trait
 * BelongsToTenant: un registro de otro hospital no resuelve y findOrFail
 * responde 404 sin revelar que existe.
 *
 * IMPORTANTE: {soap_note} NO usa binding implícito de modelo por el mismo
 * motivo que en PatientController: SubstituteBindings corre antes que el
 * middleware 'tenant'. show/update/sign/addDiagnosis reciben el id y lo
 * resuelven aquí, ya con el tenant resuelto.
 */
class SoapNoteController extends Controller
{
    /** Columnas por las que se permite ordenar (lista blanca, nunca input crudo). */
    private const SORTABLE = ['created_at', 'signed_at'];

    /** Carga perezosa del detalle: evita N+1 y expone lo que pide el contrato. */
    private const DETAIL_RELATIONS = ['diagnoses', 'prescriptions', 'doctor:id,tenant_id,user_id,specialty_id,license_number,phone', 'doctor.user:id,tenant_id,name,email', 'medicalRecord:id,tenant_id,record_number'];

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));
        $sortBy = $request->query('sort_by', 'created_at');
        $sortBy = in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'created_at';
        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        $notes = SoapNote::query()
            ->with(['diagnoses'])
            ->when($request->filled('medical_record_id'), fn ($query) => $query->where('medical_record_id', $request->integer('medical_record_id')))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('admission_id'), fn ($query) => $query->where('admission_id', $request->integer('admission_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($notes);
    }

    public function store(StoreSoapNoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->attributes->get('tenant')->id;

        $note = SoapNote::query()->create($data);

        return response()->json([
            'soap_note' => $note->load('diagnoses'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $note = SoapNote::query()->with(self::DETAIL_RELATIONS)->findOrFail($id);

        return response()->json([
            'soap_note' => $note,
        ]);
    }

    public function update(UpdateSoapNoteRequest $request, int $id): JsonResponse
    {
        $note = SoapNote::query()->findOrFail($id);

        // RF-SOAP-03: una nota firmada es un documento clínico inmodificable.
        if ($note->signed_at !== null) {
            return response()->json([
                'message' => 'La nota SOAP ya está firmada y no puede modificarse.',
            ], 422);
        }

        $note->update($request->validated());

        return response()->json([
            'soap_note' => $note->load('diagnoses'),
        ]);
    }

    public function sign(Request $request, int $id): JsonResponse
    {
        $note = SoapNote::query()->findOrFail($id);

        $validated = $request->validate([
            'electronic_sign' => ['required', 'string', 'max:255'],
        ]);

        // No se puede firmar dos veces; el sello ya puesto prevalece.
        if ($note->signed_at !== null) {
            return response()->json([
                'message' => 'La nota SOAP ya está firmada.',
            ], 422);
        }

        $note->update([
            'electronic_sign' => $validated['electronic_sign'],
            'signed_at' => now(),
        ]);

        return response()->json([
            'soap_note' => $note->load(self::DETAIL_RELATIONS),
        ]);
    }

    public function addDiagnosis(StoreDiagnosisRequest $request, int $id): JsonResponse
    {
        $note = SoapNote::query()->findOrFail($id);

        // RF-SOAP-03 aplica también a sus diagnósticos: nada cambia tras firmar.
        if ($note->signed_at !== null) {
            return response()->json([
                'message' => 'La nota SOAP ya está firmada y no admite nuevos diagnósticos.',
            ], 422);
        }

        $diagnosis = Diagnosis::query()->create(array_merge(
            $request->validated(),
            [
                'tenant_id' => $request->attributes->get('tenant')->id,
                'soap_note_id' => $note->id,
            ],
        ));

        return response()->json([
            'diagnosis' => $diagnosis->fresh(),
        ], 201);
    }
}