<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

/** RF-TA-03: se consulta antes de abrir la transacción HOSPITAL; un false termina en RechazoConfirmado(NO_AUTORIZADO). */
interface Autorizador
{
    public function permite(string $actorRefId, Operacion $operacion): bool;
}
