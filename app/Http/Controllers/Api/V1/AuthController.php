<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Crea un usuario dentro del hospital actual. Solo el rol Admin puede
     * hacerlo (ver routes/api.php); ya no existe auto-registro público.
     */
    public function register(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenant->id),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', Rule::exists('roles', 'name')->where('guard_name', 'api')],
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role'] ?? 'Recepcionista');

        return response()->json([
            'user' => $user->load('roles', 'tenant'),
        ], 201);
    }

    /**
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $tenant = $request->attributes->get('tenant');

        $user = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user->load('roles', 'tenant'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        return response()->json([
            'user' => $user->load('roles', 'tenant'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $tenantHeader = $request->header('X-Tenant-ID');
        $user = auth('api')->user();

        if ($user !== null && $tenantHeader !== null && $tenantHeader !== ''
            && (string) $user->tenant_id !== (string) $tenantHeader) {
            return response()->json([
                'message' => 'El tenant indicado no coincide con el usuario del token.',
            ], 403);
        }

        $token = JWTAuth::refresh(JWTAuth::getToken());

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
