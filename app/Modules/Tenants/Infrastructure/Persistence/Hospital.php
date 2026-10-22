<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

final class Hospital extends Model
{
    protected $connection = 'central';

    protected $table = 'hospitals';

    protected $primaryKey = 'hospital_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
