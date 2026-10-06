<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'disponible',
                    'ocupada',
                    'limpieza',
                    'mantenimiento',
                ]),
            ],
            'ward_id' => [
                'nullable',
                'integer',
            ],
        ]);

        $perPage = min((int) $request->integer('per_page', 15), 50);
        $term = $request->query('q');

        $beds = Bed::query()
            ->with('ward:id,tenant_id,name,floor,building')
            ->when(
                $validated['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $validated['ward_id'] ?? null,
                fn ($query, $wardId) => $query->where('ward_id', $wardId)
            )
            ->when($term, function ($query) use ($term): void {
                $query->whereSearch(['code', 'notes'], $term);
            })
            ->orderBy('code')
            ->paginate($perPage);

        return response()->json($beds);
    }

    public function updateStatus(Request $request, Bed $bed): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'disponible',
                    'ocupada',
                    'limpieza',
                    'mantenimiento',
                ]),
            ],
        ]);

        $bed->update([
            'status' => $validated['status'],
        ]);

        $bed->load('ward:id,tenant_id,name,floor,building');

        return response()->json([
            'message' => 'Estado de la cama actualizado correctamente.',
            'bed' => $bed,
        ]);
    }
}
