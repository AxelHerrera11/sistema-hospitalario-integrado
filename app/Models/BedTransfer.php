<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BedTransfer extends Model
{
    use BelongsToTenant;

    // La tabla solo tiene created_at.
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'admission_id',
        'from_bed_id',
        'to_bed_id',
        'authorized_by',
        'transferred_at',
        'reason',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function fromBed()
    {
        return $this->belongsTo(Bed::class, 'from_bed_id');
    }

    public function toBed()
    {
        return $this->belongsTo(Bed::class, 'to_bed_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
