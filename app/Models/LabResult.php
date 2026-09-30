<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'lab_order_item_id',
        'sample_id',
        'entered_by',
        'validated_by',
        'numeric_value',
        'text_value',
        'is_critical',
        'is_abnormal',
        'resulted_at',
        'validated_at',
        'sent_to_emr',
        'sent_to_emr_at',
    ];

    protected $casts = [
        'numeric_value' => 'decimal:4',
        'is_critical' => 'boolean',
        'is_abnormal' => 'boolean',
        'sent_to_emr' => 'boolean',
        'resulted_at' => 'datetime',
        'validated_at' => 'datetime',
        'sent_to_emr_at' => 'datetime',
    ];

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function orderItem()
    {
        return $this->belongsTo(LabOrderItem::class, 'lab_order_item_id');
    }

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }
}
