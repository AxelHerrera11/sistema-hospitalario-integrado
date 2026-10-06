<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LabTestRequest;
use App\Models\LabTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogo de pruebas de laboratorio — Área 7, submódulo CAT.
 *
 * Las pruebas no se borran: se desactivan con active=false (RF-CAT-03), así
 * las órdenes que ya las usan no cambian. El aislamiento por hospital lo
 * aplica BelongsToTenant, por eso una prueba de otro hospital responde 404.
 */
class LabTestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));

        $labTests = LabTest::query()
            ->when($request->query('q'), fn ($query, $term) => $query->whereSearch(['name', 'category'], $term))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->has('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($labTests);
    }

    public function show(LabTest $labTest): JsonResponse
    {
        return response()->json([
            'lab_test' => $labTest,
        ]);
    }

    public function store(LabTestRequest $request): JsonResponse
    {
        $labTest = LabTest::query()->create($request->validated());

        return response()->json([
            'lab_test' => $labTest->fresh(),
        ], 201);
    }

    public function update(LabTestRequest $request, LabTest $labTest): JsonResponse
    {
        $labTest->update($request->validated());

        return response()->json([
            'lab_test' => $labTest->fresh(),
        ]);
    }
}
