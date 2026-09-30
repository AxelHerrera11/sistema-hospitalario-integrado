<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'generic_name',
        'category',
        'presentation',
        'concentration',
        'active',
    ];

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
