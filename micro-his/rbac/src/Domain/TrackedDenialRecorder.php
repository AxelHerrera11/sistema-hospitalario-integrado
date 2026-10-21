<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

/**
 * Decorador de DenialRecorder que expone si el último registro falló.
 *
 * El motor Authorizer atrapa y silencia los fallos de auditoría (falla segura),
 * por lo que un consumidor no puede distinguir una denegación ordinaria de un
 * fallo del almacén. Este decorador marca el fallo y permite al caso de uso
 * responder 503 "registro_no_disponible" manteniendo allowed=false y trace_id.
 */
final class TrackedDenialRecorder implements DenialRecorder
{
    private bool $lastFailed = false;

    public function __construct(private readonly DenialRecorder $inner)
    {
    }

    public function record(array $denial): void
    {
        $this->lastFailed = true;
        $this->inner->record($denial);
        $this->lastFailed = false;
    }

    /** ¿Falló el último intento de registro? */
    public function failed(): bool
    {
        return $this->lastFailed;
    }
}
