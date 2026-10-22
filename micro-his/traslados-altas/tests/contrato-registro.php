<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Domain\PlanTraslado;

/**
 * Contrato del Repository RegistroInternacion: las mismas comprobaciones, solo a través del puerto, para cualquier
 * adaptador. $preparar devuelve un registro nuevo con el escenario estándar y sus ids:
 * episodio hospitalario abierto con la estancia en la cama A; B libre; C en mantenimiento; D ocupada por otro episodio.
 *
 * @param Closure(): array{0: RegistroInternacion, 1: array{estancia: string, episodio: string, a: string, b: string, c: string, d: string, actor: string}} $preparar
 */
function contratoRegistro(string $adaptador, Closure $preparar): void
{
    [$r, $id] = $preparar();
    $estado = $r->leerPorEstancia($id['estancia']);
    check("{$adaptador}: lee episodio y estancia abierta", $estado !== null && [$estado->episodioId, $estado->tipoEpisodio, $estado->estadoEpisodio,
        $estado->estanciaAbiertaId, $estado->camaActualId] === [$id['episodio'], 'inpatient', 'open', $id['estancia'], $id['a']]);
    check("{$adaptador}: estancia inexistente → null", $r->leerPorEstancia('00000000-0000-4000-8000-00000000dead') === null);
    $t = $r->ahora();
    check("{$adaptador}: ahora() es posterior al inicio de la estancia", $t > $estado->inicioEstancia);
    $b = $r->leerCama($id['b'], $t);
    $c = $r->leerCama($id['c'], $t);
    check("{$adaptador}: cama libre, en mantenimiento, ocupada e inexistente", $b?->libreDesdeT === true && $b->estado === 'available'
        && $c?->estado === 'maintenance' && $r->leerCama($id['d'], $t)?->libreDesdeT === false
        && $r->leerCama('00000000-0000-4000-8000-00000000beef', $t) === null);

    $plan = new PlanTraslado($id['estancia'], $id['a'], $id['episodio'], $id['b'], 'Contrato', $t);
    $r->iniciar();
    $r->cerrarEstancia($id['estancia'], $t);
    $r->abrirEstancia($plan, $id['actor']);
    $r->revertir();
    check("{$adaptador}: revertir deshace el cierre y la apertura", $r->leerPorEstancia($id['estancia'])?->estanciaAbiertaId === $id['estancia']);

    $r->iniciar();
    $r->cerrarEstancia($id['estancia'], $t);
    $nueva = $r->abrirEstancia($plan, $id['actor']);
    $r->confirmar();
    $despues = $r->leerPorEstancia($id['estancia']);
    check("{$adaptador}: confirmar deja abierta la estancia destino en B", $despues?->estanciaAbiertaId === $nueva && $despues->camaActualId === $id['b']);
    check("{$adaptador}: B queda ocupada desde T y A libre desde T", $r->leerCama($id['b'], $t)?->libreDesdeT === false && $r->leerCama($id['a'], $t)?->libreDesdeT === true);

    $r->iniciar();
    $t2 = $r->ahora();
    $r->cerrarEstancia($nueva, $t2);
    $r->cerrarEpisodio($id['episodio'], $t2);
    $r->confirmar();
    $cerrado = $r->leerPorEstancia($nueva);
    check("{$adaptador}: cerrar estancia y episodio deja el episodio closed sin estancia abierta", $cerrado?->estadoEpisodio === 'closed'
        && $cerrado->estanciaAbiertaId === null);
}
