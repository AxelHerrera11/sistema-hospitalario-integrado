<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $term = $validated['q'] ?? null;
        $perPage = $validated['per_page'] ?? 15;
        $page = $validated['page'] ?? null;

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->with(['roles' => function (MorphToMany $query): void {
                $query->select(['roles.id', 'roles.name'])->orderBy('roles.name');
            }])
            ->when($term !== null && $term !== '', fn ($query) => $query->whereSearch(['name', 'email'], $term))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values(),
            ]);

        return response()->json($users);
    }
}
