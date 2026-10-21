<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Application\Administration\ListHospitalUsers;
use App\Modules\Tenants\Application\Administration\ManageHospitalMembership;
use App\Modules\Tenants\Domain\Administration\AdminFailure;
use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HospitalUserController extends Controller
{
    public function __construct(private readonly ManageHospitalMembership $memberships) {}

    public function index(Request $request, ListHospitalUsers $users): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:128'],
            'status' => ['nullable', 'in:active,inactive'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $users->execute(
            $this->actorId($request),
            $this->hospitalId($request),
            $validated['search'] ?? null,
            $validated['status'] ?? null,
            (int) ($validated['per_page'] ?? 25),
        );

        return response()->json($result);
    }

    public function assign(Request $request, string $user): JsonResponse
    {
        $validated = $request->validate([
            'role_code' => ['required', 'string', 'max:32'],
        ]);

        try {
            $result = $this->memberships->assign(
                $this->actorId($request),
                $this->actorRoles($request),
                $this->hospitalId($request),
                $user,
                $validated['role_code'],
            );
        } catch (AdminFailure $failure) {
            return $this->failure($failure);
        }

        return response()->json($result, 201);
    }

    public function revoke(Request $request, string $user, string $role): JsonResponse
    {
        try {
            $result = $this->memberships->revoke(
                $this->actorId($request),
                $this->actorRoles($request),
                $this->hospitalId($request),
                $user,
                $role,
            );
        } catch (AdminFailure $failure) {
            return $this->failure($failure);
        }

        return response()->json($result);
    }

    public function deactivate(Request $request, string $user): JsonResponse
    {
        try {
            $this->memberships->deactivateInHospital(
                $this->actorId($request),
                $this->hospitalId($request),
                $user,
            );
        } catch (AdminFailure $failure) {
            return $this->failure($failure);
        }

        return response()->json(['message' => 'Acceso del usuario al hospital revocado.']);
    }

    public function revokeSession(Request $request, string $session): JsonResponse
    {
        try {
            $this->memberships->revokeForeignSession(
                $this->actorId($request),
                $this->hospitalId($request),
                $session,
            );
        } catch (AdminFailure $failure) {
            return $this->failure($failure);
        }

        return response()->json(['message' => 'Sesión revocada.']);
    }

    private function actorId(Request $request): string
    {
        return (string) $request->user()->getAuthIdentifier();
    }

    private function actorRoles(Request $request): array
    {
        return (array) $request->attributes->get('auth_roles', []);
    }

    private function hospitalId(Request $request): string
    {
        /** @var Hospital $hospital */
        $hospital = $request->attributes->get('tenant');

        return (string) $hospital->getKey();
    }

    private function failure(AdminFailure $failure): JsonResponse
    {
        return response()->json([
            'error' => $failure->errorCode,
            'message' => $failure->getMessage(),
        ], $failure->status);
    }
}
