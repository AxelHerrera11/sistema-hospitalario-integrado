<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

final class Credential extends Model
{
    protected $connection = 'central';

    protected $table = 'auth_credentials';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
