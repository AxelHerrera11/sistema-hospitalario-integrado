<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'dpi',
        'nit',
        'phone',
        'email',
        'address',
        'insurance_company',
        'insurance_policy',
        'emergency_contact_name',
        'emergency_contact_phone',
        'blood_type',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function allergies()
    {
        return $this->hasMany(Allergy::class);
    }

    public function medications()
    {
        return $this->hasMany(Prescription::class);
    }

    public function vitalSigns()
    {
        return $this->hasManyThrough(
            VitalSign::class,
            MedicalRecord::class,
            'patient_id',
            'medical_record_id',
            'id',
            'id'
        )->orderByDesc('measured_at');
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function currentAdmission()
    {
        return $this->hasOne(Admission::class)->where('status', 'activa');
    }

    public function getCurrentBedAttribute()
    {
        return $this->currentAdmission?->bed;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function criticalAlerts()
    {
        return $this->hasMany(CriticalAlert::class);
    }
}
