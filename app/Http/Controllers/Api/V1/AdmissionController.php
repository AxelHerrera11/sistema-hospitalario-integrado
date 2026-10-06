<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdmissionRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AdmissionService;
use Illuminate\Http\JsonResponse;

class AdmissionController extends Controller
{
    public function __construct(
        private readonly AdmissionService $admissions
    ) {}

    public function store(StoreAdmissionRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        /** @var User $user */
        $user = auth('api')->user();

        $admission = $this->admissions->create(
            $request->validated(),
            $user,
            $tenant
        );

        return response()->json([
            'admission' => [
                'id' => $admission->id,
                'code' => $admission->code,
                'admitted_at' => $admission->admitted_at,
                'status' => $admission->status,

                'patient' => [
                    'id' => $admission->patient->id,
                    'code' => $admission->patient->code,
                    'first_name' => $admission->patient->first_name,
                    'last_name' => $admission->patient->last_name,
                    'birth_date' => $admission->patient->birth_date?->toDateString(),
                    'gender' => $admission->patient->gender,
                ],

                'bed' => [
                    'id' => $admission->bed->id,
                    'code' => $admission->bed->code,
                    'status' => $admission->bed->status,
                    'ward' => [
                        'id' => $admission->bed->ward->id,
                        'name' => $admission->bed->ward->name,
                    ],
                ],

                'doctor' => [
                    'id' => $admission->doctor->id,
                    'license_number' => $admission->doctor->license_number,
                    'name' => $admission->doctor->user?->name,
                ],
            ],
        ], 201);
    }
}
