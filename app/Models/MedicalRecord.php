<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'record_number',
        'opened_at',
        'background',
        'family_background',
        'surgical_history',
        'obstetric_history',
    ];

    protected $casts = [
        'opened_at' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class);
    }
}

