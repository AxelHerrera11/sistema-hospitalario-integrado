<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $term = $request->query('q');

        $wards = Ward::query()
            ->withCount([
                'beds',
                'beds as available_beds_count' => fn ($query) => $query->where('status', 'disponible'),
            ])
            ->when($term, function ($query) use ($term): void {
                $query->whereSearch(
                    ['name', 'floor', 'building'],
                    $term
                );
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($wards);
    }

    public function beds(Request $request, Ward $ward): JsonResponse
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
        ]);

        $beds = $ward->beds()
            ->when(
                $validated['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->orderBy('code')
            ->get();

        return response()->json([
            'ward' => $ward,
            'beds' => $beds,
        ]);
    }
}
