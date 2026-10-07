<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllergyIndexRequest;
use App\Http\Requests\AllergyRequest;
use App\Models\Allergy;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class AllergyController extends Controller
{
    public function index(AllergyIndexRequest $request, Patient $patient): JsonResponse
    {
        $filters = $request->validated();

        $allergies = $patient->allergies()
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('active', $filters['active']))
            ->when(isset($filters['allergen_type']), fn ($query) => $query->where('allergen_type', $filters['allergen_type']))
            ->when(isset($filters['severity']), fn ($query) => $query->where('severity', $filters['severity']))
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($allergies);
    }

    public function store(AllergyRequest $request, Patient $patient): JsonResponse
    {
        $allergy = $patient->allergies()->create([
            ...$request->validated(),
            'tenant_id' => $request->attributes->get('tenant')->id,
            'registered_by' => auth()->id(),
        ]);

        return response()->json(['allergy' => $allergy], 201);
    }

    public function update(AllergyRequest $request, Allergy $allergy): JsonResponse
    {
        $allergy->update($request->validated());

        return response()->json(['allergy' => $allergy->fresh()]);
    }
}
