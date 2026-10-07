<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representacion minima del paciente para las respuesta de otras areas
 * como laboratorio,admisiones, notas SOAP, preinscripciones, etc.
 * Se excluyen datos sensibles como telefono, direccion, email, etc.
 * Para obtener la informacion completa del paciente se debe usar el recurso
 * GET api/v1/patients/{patient}.
 */
class PatientSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
        ];
    }
}
