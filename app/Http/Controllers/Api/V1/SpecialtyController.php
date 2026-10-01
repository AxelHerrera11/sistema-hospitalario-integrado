<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialtyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 50);

        $specialties = Specialty::query()
            ->when($request->query('q'), fn ($query, $term) => $query->whereSearch(['name', 'description'], $term))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($specialties);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')->where('tenant_id', $tenant->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $specialty = Specialty::query()->create($validated);

        return response()->json([
            'specialty' => $specialty,
        ], 201);
    }

    public function update(Request $request, Specialty $specialty): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($specialty->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $specialty->update($validated);

        return response()->json([
            'specialty' => $specialty->fresh(),
        ]);
    }
}
