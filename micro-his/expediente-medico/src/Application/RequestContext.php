<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/**
 * Quién pide y en qué hospital. Separa tres cosas que el sistema real guarda en lugares distintos:
 *  - actorId: identidad autenticada (usuario CENTRAL); la usan RBAC y la auditoría.
 *  - localUserRefId: referencia del mismo usuario en la base HOSPITAL (local_user_refs); la usa el vínculo asistencial.
 *  - hospitalId: tenant en el que actúa.
 * En el micro-HIS ambas identidades coinciden (X-User-ID); en Laravel las resuelve el controlador desde el JWT.
 */
final readonly class RequestContext
{
    public function __construct(
        public string $actorId,
        public string $hospitalId,
        public ?string $correlationId = null,
        private ?string $localUserRefId = null,
    ) {
    }

    public function localUserRef(): string
    {
        return $this->localUserRefId ?? $this->actorId;
    }
}
