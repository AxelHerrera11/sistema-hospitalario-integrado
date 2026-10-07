<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicationRequest;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));

        $medications = Medication::query()
            ->when(
                $request->query('q'),
                fn ($query, $term) => $query->whereSearch(['name', 'generic_name', 'category'], $term)
            )
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->has('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($medications);
    }

    public function show(Medication $medication): JsonResponse
    {
        return response()->json(['medication' => $medication]);
    }

    public function store(MedicationRequest $request): JsonResponse
    {
        $medication = Medication::query()->create($request->validated());

        return response()->json(['medication' => $medication->fresh()], 201);
    }

    public function update(MedicationRequest $request, Medication $medication): JsonResponse
    {
        $medication->update($request->validated());

        return response()->json(['medication' => $medication->fresh()]);
    }
}
