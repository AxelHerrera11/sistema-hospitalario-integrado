<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use PDO;

/**
 * Las claves del esquema real son uuid: PostgreSQL rechaza con un error de sintaxis cualquier otro texto, y ese error no
 * debe llegar al cliente como 500/503 ni revelar la base. Un id con otro formato simplemente no existe. El SQLite de las
 * suites guarda ids de texto libre, por eso solo se exige el formato en PostgreSQL.
 */
final class PostgresIds
{
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function usable(PDO $pdo, string ...$ids): bool
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
            return true;
        }
        foreach ($ids as $id) {
            if (preg_match(self::UUID, $id) !== 1) {
                return false;
            }
        }
        return true;
    }
}
