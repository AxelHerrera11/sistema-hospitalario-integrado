<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $doctor = Doctor::query()->create($request->validated());

        return response()->json([
            'doctor' => $doctor->load(['user:id,tenant_id,name,email', 'specialty:id,tenant_id,name,description']),
        ], 201);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $doctor->update($request->validated());

        return response()->json([
            'doctor' => $doctor->fresh()->load(['user:id,tenant_id,name,email', 'specialty:id,tenant_id,name,description']),
        ]);
    }
}
