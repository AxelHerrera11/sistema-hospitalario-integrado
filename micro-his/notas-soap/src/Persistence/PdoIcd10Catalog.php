<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\CatalogUnavailable;
use MicroHis\NotasSoap\Application\Icd10Catalog;
use PDO;

/** Catálogo CIE-10 leído de una tabla local. Un fallo de la BD se traduce a CatalogUnavailable (falla segura). */
final class PdoIcd10Catalog implements Icd10Catalog
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function description(string $code): ?string
    {
        try {
            $stmt = $this->pdo->prepare('SELECT description FROM icd10_codes WHERE code = ? AND active = 1');
            $stmt->execute([$code]);
            return $stmt->fetchColumn() ?: null;
        } catch (\PDOException $e) {
            throw new CatalogUnavailable('icd10_no_disponible', 0, $e);
        }
    }
}
