<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * El almacén de auditoría no pudo escribir o leer.
 *
 * El mensaje es un código interno estable (por ejemplo "audit_write_failed");
 * nunca contiene SQL, credenciales ni el detalle del driver.
 */
final class AuditStorageFailure extends \RuntimeException
{
}
