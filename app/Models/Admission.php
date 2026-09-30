<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'bed_id',
        'doctor_id',
        'admitted_by',
        'code',
        'admitted_at',
        'discharged_at',
        'status',
        'discharge_type',
        'discharge_notes',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('discharged_at');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function admittedBy()
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function transfers()
    {
        return $this->hasMany(BedTransfer::class);
    }
}
