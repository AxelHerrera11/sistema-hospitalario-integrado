<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Infrastructure;

use MicroHIS\Documentacion\Domain\Documento;
use MicroHIS\Documentacion\Domain\DocumentoRepository;
use MicroHIS\Documentacion\Domain\ErrorPersistenciaException;
use PDO;

// Adaptador Repository con PDO y sentencias preparadas. Sin reglas de negocio.
final class DocumentoRepositoryPDO implements DocumentoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS documentos (id TEXT PRIMARY KEY, titulo TEXT, roles TEXT)');
    }

    public function todos(): array
    {
        try {
            $filas = $this->pdo->query('SELECT id, titulo, roles FROM documentos')->fetchAll();
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
        return array_map(fn (array $f): Documento => $this->hidratar($f), $filas);
    }

    public function guardar(Documento $documento): void
    {
        try {
            $sql = 'INSERT INTO documentos (id, titulo, roles) VALUES (:id, :titulo, :roles)';
            $this->pdo->prepare($sql)->execute([
                ':id' => $documento->id, ':titulo' => $documento->titulo, ':roles' => implode(',', $documento->roles),
            ]);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
    }

    private function hidratar(array $fila): Documento
    {
        $roles = array_map('trim', explode(',', (string) $fila['roles']));
        return Documento::publicar((string) $fila['id'], (string) $fila['titulo'], $roles);
    }
}
