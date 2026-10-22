<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use DateTimeImmutable;
use MicroHis\TrasladosAltas\Domain\CamaDestino;
use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\PlanTraslado;

/**
 * Puerto de la base HOSPITAL para una sola transacción (RNF-TA-03). Las lecturas bloquean las filas que la operación
 * va a cambiar, para que dos solicitudes sobre la misma estancia no tengan éxito ambas (RNF-TA-04).
 * Todo método salvo confirmar() y revertir() lanza FalloPersistencia si HOSPITAL falla.
 */
interface RegistroInternacion
{
    public function iniciar(): void;

    /** Episodio y estancia abierta del episodio al que pertenece la estancia esperada; null si esa estancia no existe. */
    public function leerPorEstancia(string $estanciaId): ?EstadoInternacion;

    /** Marca temporal T de la operación, tomada del reloj de HOSPITAL. */
    public function ahora(): DateTimeImmutable;

    /** null si la cama no existe; bloquea la fila para serializar traslados hacia la misma cama. */
    public function leerCama(string $camaId, DateTimeImmutable $t): ?CamaDestino;

    public function cerrarEstancia(string $estanciaId, DateTimeImmutable $t): void;

    /** Abre [T, ∞) en la cama destino para el mismo episodio y devuelve el id de la nueva estancia. */
    public function abrirEstancia(PlanTraslado $plan, string $actorRefId): string;

    public function cerrarEpisodio(string $episodioId, DateTimeImmutable $t): void;

    /** @throws ConfirmacionIncierta si no se sabe si el COMMIT se aplicó; FalloPersistencia si se sabe que no. */
    public function confirmar(): void;

    /** Nunca lanza: si la conexión se perdió, HOSPITAL ya descartó la transacción no confirmada. */
    public function revertir(): void;
}
