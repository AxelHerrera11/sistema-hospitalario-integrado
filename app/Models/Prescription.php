<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'soap_note_id',
        'patient_id',
        'doctor_id',
        'medication_id',
        'dose',
        'frequency',
        'duration_days',
        'route',
        'indications',
        'blocked',
        'blocked_reason',
        'electronic_sign',
        'signed_at',
    ];

    protected $casts = [
        'blocked' => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}

