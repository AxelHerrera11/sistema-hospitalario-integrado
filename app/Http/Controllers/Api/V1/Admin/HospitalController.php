<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Application\Administration\RegisterHospital;
use App\Modules\Tenants\Domain\Administration\AdminFailure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HospitalController extends Controller
{
    public function __construct(private readonly RegisterHospital $hospitals) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'network_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
        ]);

        try {
            $hospital = $this->hospitals->execute(
                (string) $request->user()->getAuthIdentifier(),
                (string) $request->attributes->get('tenant')->getKey(),
                $validated['network_id'],
                $validated['code'],
                $validated['name'],
                $validated['timezone'],
            );
        } catch (AdminFailure $failure) {
            return response()->json([
                'error' => $failure->errorCode,
                'message' => $failure->getMessage(),
            ], $failure->status);
        }

        return response()->json($hospital, 201);
    }
}
