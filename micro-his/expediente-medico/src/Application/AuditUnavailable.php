<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/** La bitácora no registró el evento. Para un acceso exitoso al expediente es bloqueante: sin auditoría no hay datos. */
final class AuditUnavailable extends \RuntimeException
{
}
