<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasUuids;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $connection = 'hospital';

    protected $table = 'appointments';

    protected $primaryKey = 'appointment_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** timestamptz: con desfase, para que PostgreSQL no lo lea en la zona horaria de su sesión. */
    protected $dateFormat = 'Y-m-d H:i:sP';

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
