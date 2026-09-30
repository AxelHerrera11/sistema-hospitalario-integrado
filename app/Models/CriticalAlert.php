<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CriticalAlert extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lab_result_id',
        'patient_id',
        'notified_user_id',
        'alert_type',
        'message',
        'acknowledged',
        'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function labResult()
    {
        return $this->belongsTo(LabResult::class);
    }

    public function notifiedUser()
    {
        return $this->belongsTo(User::class, 'notified_user_id');
    }
}
