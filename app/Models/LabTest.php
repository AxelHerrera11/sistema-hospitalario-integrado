<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'unit',
        'reference_min',
        'reference_max',
        'critical_min',
        'critical_max',
        'turnaround_min',
        'active',
    ];

    protected $casts = [
        'reference_min' => 'decimal:4',
        'reference_max' => 'decimal:4',
        'critical_min' => 'decimal:4',
        'critical_max' => 'decimal:4',
        'active' => 'boolean',
    ];

    public function orderItems()
    {
        return $this->hasMany(LabOrderItem::class);
    }
}
