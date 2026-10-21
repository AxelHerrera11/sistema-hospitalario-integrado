<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

final class CentralUser extends Authenticatable implements JWTSubject
{
    protected $connection = 'central';

    protected $table = 'central_users';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'last_authenticated_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function getAuthPassword(): string
    {
        return '';
    }
}
