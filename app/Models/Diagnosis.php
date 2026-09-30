<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use BelongsToTenant;

    protected $table = 'diagnoses';

    protected $fillable = [
        'tenant_id',
        'soap_note_id',
        'cie10_code',
        'description',
        'type',
    ];

    public function soapNote()
    {
        return $this->belongsTo(SoapNote::class);
    }
}
