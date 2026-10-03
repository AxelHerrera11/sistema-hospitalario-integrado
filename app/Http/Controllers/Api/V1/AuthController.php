<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const EMAIL_TAKEN = 'El correo electrónico ya está registrado en este hospital.';

    /**
     * Crea un usuario dentro del hospital actual. Solo el rol Admin puede
     * hacerlo (ver routes/api.php); ya no existe auto-registro público.
     */
    public function register(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'bail', 'required', 'email', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (User::query()->where('email', $value)->exists()) {
                        $fail(self::EMAIL_TAKEN);
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', Rule::exists('roles', 'name')->where('guard_name', 'api')],
        ]);

        try {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isTenantEmailUniqueViolation($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'email' => [self::EMAIL_TAKEN],
            ]);
        }

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
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
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
        try {
            $jwt = JWTAuth::parseToken();
            $manager = $jwt->manager();
            $payload = $manager->setRefreshFlow()->decode($jwt->getToken());
        } catch (JWTException) {
            return response()->json([
                'message' => 'Token inválido o fuera del período de renovación.',
            ], 401);
        } finally {
            if (isset($manager)) {
                $manager->setRefreshFlow(false);
            }
        }

        $tenant = $request->attributes->get('tenant');
        $tokenTenant = $payload->get('tenant_id');

        if ((string) $tokenTenant !== (string) $tenant->getKey()) {
            return response()->json([
                'message' => 'El tenant indicado no coincide con el usuario del token.',
            ], 403);
        }

        if (User::query()->find($payload->get('sub')) === null) {
            return response()->json([
                'message' => 'Token inválido o expirado.',
            ], 401);
        }

        try {
            $token = $jwt->claims(['tenant_id' => $tokenTenant])->refresh();
        } catch (JWTException) {
            return response()->json([
                'message' => 'Token inválido o fuera del período de renovación.',
            ], 401);
        } finally {
            $jwt->claims([]);
            $jwt->manager()->setRefreshFlow(false);
        }

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

    private function normalizeEmail(Request $request): void
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower($email)]);
        }
    }

    private function isTenantEmailUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();

        return match ($sqlState) {
            '23505' => str_contains($message, 'users_tenant_id_email_unique'),
            '23000' => str_contains($message, 'UNIQUE constraint failed: users.tenant_id, users.email'),
            default => false,
        };
    }
}
