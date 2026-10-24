<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Persistence;

use MicroHIS\Documentacion\Domain\Documento;
use MicroHIS\Documentacion\Domain\DocumentoRepository;
use MicroHIS\Documentacion\Domain\ErrorPersistenciaException;
use PDO;

/**
 * Adaptador PDO de DocumentoRepository. Sin reglas de negocio: solo traduce filas.
 */
final class DocumentoRepositoryPDO implements DocumentoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS documentos (
            id TEXT PRIMARY KEY,
            titulo TEXT NOT NULL,
            roles TEXT NOT NULL
        )');
    }

    public function todos(): array
    {
        try {
            $filas = $this->pdo->query('SELECT id, titulo, roles FROM documentos ORDER BY id')->fetchAll();
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
        return array_map(fn (array $f): Documento => $this->hidratar($f), $filas);
    }

    public function guardar(Documento $documento): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO documentos (id, titulo, roles) VALUES (:id, :titulo, :roles)'
            );
            $stmt->execute([
                ':id' => $documento->id,
                ':titulo' => $documento->titulo,
                ':roles' => implode(',', $documento->roles),
            ]);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
    }

    public function buscar(string $id): ?Documento
    {
        $stmt = $this->pdo->prepare('SELECT id, titulo, roles FROM documentos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $this->hidratar($fila);
    }

    private function hidratar(array $fila): Documento
    {
        return Documento::publicar(
            (string) $fila['id'],
            (string) $fila['titulo'],
            array_map('trim', explode(',', (string) $fila['roles']))
        );
    }
}
