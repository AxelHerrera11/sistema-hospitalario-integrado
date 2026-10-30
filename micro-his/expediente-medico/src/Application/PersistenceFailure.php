<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/** El almacén del hospital no respondió. El mensaje es un código interno: nunca SQL, credenciales ni datos clínicos. */
final class PersistenceFailure extends \RuntimeException
{
}
