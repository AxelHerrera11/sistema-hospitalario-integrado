<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Application\Administration;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ListHospitalUsers
{
    public function __construct(private readonly AuditLogRepositoryInterface $audit) {}

    public function execute(
        string $actorId,
        string $hospitalId,
        ?string $search,
        ?string $status,
        int $perPage,
    ): LengthAwarePaginator {
        $users = DB::connection('central')->table('central_users as u')
            ->join('hospital_memberships as m', 'm.user_id', '=', 'u.user_id')
            ->where('m.hospital_id', $hospitalId)
            ->where('m.status', 'active')
            ->where('m.valid_from', '<=', now())
            ->where(fn ($query) => $query
                ->whereNull('m.valid_to')
                ->orWhere('m.valid_to', '>', now()))
            ->when($status !== null, fn ($query) => $query->where('u.status', $status))
            ->when($search !== null, function ($query) use ($search): void {
                $term = '%'.mb_strtolower(trim($search)).'%';
                $query->where(fn ($matching) => $matching
                    ->whereRaw('LOWER(u.email) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(u.display_name) LIKE ?', [$term]));
            })
            ->select('u.user_id', 'u.email', 'u.display_name', 'u.status')
            ->distinct()
            ->orderBy('u.display_name')
            ->orderBy('u.user_id')
            ->paginate($perPage);

        $userIds = collect($users->items())->pluck('user_id')->all();
        $roles = DB::connection('central')->table('hospital_memberships')
            ->where('hospital_id', $hospitalId)
            ->whereIn('user_id', $userIds)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>', now()))
            ->orderBy('role_code')
            ->get(['user_id', 'role_code'])
            ->groupBy('user_id');

        $users->setCollection($users->getCollection()->map(fn ($user): array => [
            'user_id' => (string) $user->user_id,
            'email' => $user->email,
            'display_name' => $user->display_name,
            'status' => $user->status,
            'roles' => $roles->get($user->user_id, collect())
                ->pluck('role_code')
                ->unique()
                ->values()
                ->all(),
        ]));

        $this->audit->logRaw('users.listed', [
            'user_id' => $actorId,
            'hospital_id' => $hospitalId,
            'tenant_id' => $hospitalId,
            'permission' => 'usuarios.ver',
            'reason' => 'hospital_user_list',
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'result_count' => $users->count(),
            'result' => 'success',
        ], 'info');

        return $users;
    }
}
