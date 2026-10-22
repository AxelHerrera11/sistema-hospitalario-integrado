<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

/** RF-TA-08: ejecutar() informa exactamente uno de cuatro resultados; ninguno se deduce de otro. */
interface ResultadoOperacion
{
    /** true solo cuando HOSPITAL confirmó la escritura; un resultado incierto nunca es éxito. */
    public function confirmoCambios(): bool;
}
