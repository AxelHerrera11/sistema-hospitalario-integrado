<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

/**
 * Contrato común de la Semana 2 (LSP): traslado y alta se usan igual. Precondición: una solicitud ya válida.
 * Postcondición: exactamente un ResultadoOperacion; ante un rechazo o un fallo confirmado, HOSPITAL queda sin cambios.
 */
interface OperacionConLiberacionDeCama
{
    public function ejecutar(): ResultadoOperacion;
}
