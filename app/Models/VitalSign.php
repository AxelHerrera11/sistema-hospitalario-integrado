<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'medical_record_id',
        'admission_id',
        'registered_by',
        'temperature',
        'heart_rate',
        'respiratory_rate',
        'systolic_bp',
        'diastolic_bp',
        'oxygen_saturation',
        'weight',
        'height',
        'glucose',
        'has_alert',
        'alert_details',
        'measured_at',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
        'temperature' => 'decimal:1',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'glucose' => 'decimal:2',
        'oxygen_saturation' => 'decimal:2',
        'has_alert' => 'boolean',
        'alert_details' => 'array',
    ];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
