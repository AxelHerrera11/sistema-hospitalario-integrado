<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $term = $request->query('q');

        $doctors = Doctor::query()
            ->with(['user:id,tenant_id,name,email', 'specialty:id,tenant_id,name,description'])
            ->when($term, function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->whereSearch(['license_number', 'phone'], $term)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->whereSearch(['name', 'email'], $term))
                        ->orWhereHas('specialty', fn ($specialtyQuery) => $specialtyQuery->whereSearch('name', $term));
                });
            })
            ->orderBy('license_number')
            ->paginate($perPage);

        return response()->json($doctors);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', $tenant->id),
                Rule::unique('doctors', 'user_id')->where('tenant_id', $tenant->id),
            ],
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('tenant_id', $tenant->id),
            ],
            'license_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('doctors', 'license_number')->where('tenant_id', $tenant->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $doctor = Doctor::query()->create($validated);

        return response()->json([
            'doctor' => $doctor->load(['user:id,tenant_id,name,email', 'specialty:id,tenant_id,name,description']),
        ], 201);
    }

    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('tenant_id', $tenant->id),
            ],
            'license_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('doctors', 'license_number')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($doctor->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $doctor->update($validated);

        return response()->json([
            'doctor' => $doctor->fresh()->load(['user:id,tenant_id,name,email', 'specialty:id,tenant_id,name,description']),
        ]);
    }
}
