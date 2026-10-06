<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * La tabla lab_order_items no tiene tenant_id: hereda el aislamiento de su
 * orden (lab_orders). Consúltala siempre a través de LabOrder::items().
 */
class LabOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_order_id',
        'lab_test_id',
        'status',
    ];

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function results()
    {
        return $this->hasMany(LabResult::class);
    }
}
