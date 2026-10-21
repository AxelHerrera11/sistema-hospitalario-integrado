<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

final class AuthSession extends Model
{
    protected $connection = 'central';

    protected $table = 'auth_sessions';

    protected $primaryKey = 'session_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'issued_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
        'revoked_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
