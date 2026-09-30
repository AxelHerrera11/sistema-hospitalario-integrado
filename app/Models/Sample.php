<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Sample extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lab_order_id',
        'received_by',
        'barcode',
        'sample_type',
        'collected_at',
        'received_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
