<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\CatalogUnavailable;
use MicroHis\NotasSoap\Application\Icd10Catalog;
use PDO;

/** Catálogo CIE-10 leído de una tabla local, con `active boolean` como en PostgreSQL (`active = 1` fallaría con 42883). Un fallo de la BD se traduce a CatalogUnavailable (falla segura). */
final class PdoIcd10Catalog implements Icd10Catalog
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function description(string $code): ?string
    {
        try {
            $stmt = $this->pdo->prepare('SELECT description FROM icd10_codes WHERE code = ? AND active IS TRUE');
            $stmt->execute([$code]);
            $description = $stmt->fetchColumn() ?: null;
            // Tabla migrada pero sin códigos activos (aún sin cargar): es un catálogo no disponible, no un código inválido.
            $description !== null || $this->pdo->query('SELECT 1 FROM icd10_codes WHERE active IS TRUE LIMIT 1')->fetchColumn() !== false
                || throw new CatalogUnavailable('icd10_sin_cargar');
            return $description;
        } catch (\PDOException $e) {
            throw new CatalogUnavailable('icd10_no_disponible', 0, $e);
        }
    }
}
