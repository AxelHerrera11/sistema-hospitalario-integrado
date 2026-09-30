<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'soap_note_id',
        'medical_record_id',
        'ordered_by',
        'code',
        'priority',
        'status',
        'clinical_info',
        'ordered_at',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    public function soapNote()
    {
        return $this->belongsTo(SoapNote::class, 'soap_note_id');
    }

    public function items()
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function samples()
    {
        return $this->hasMany(Sample::class);
    }
}
