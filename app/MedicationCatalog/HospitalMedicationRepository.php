<?php

declare(strict_types=1);

namespace App\MedicationCatalog;

use Closure;
use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;
use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;
use PDO;
use Throwable;

/**
 * Repository del catálogo sobre la conexión HOSPITAL del hospital activo. Abre la conexión en el primer uso, no al
 * construir el controlador (que Laravel instancia antes de los middlewares), y convierte un fallo de conexión en
 * PersistenceFailure para responder 503 y no 500.
 */
final class HospitalMedicationRepository implements MedicationRepository
{
    private ?PdoMedicationCatalog $adapter = null;
    private ?PDO $pdo = null;

    /** @param Closure(): PDO $connect */
    public function __construct(private readonly Closure $connect)
    {
    }

    public function findByCode(string $code): ?Medication
    {
        return $this->adapter()->findByCode($code);
    }

    public function findById(string $id): ?Medication
    {
        return $this->adapter()->findById($id);
    }

    public function insert(Medication $medication): Medication
    {
        return $this->adapter()->insert($medication);
    }

    public function search(string $term, int $limit): array
    {
        return $this->adapter()->search($term, $limit);
    }

    public function updateValidity(string $id, Validity $validity): void
    {
        $this->adapter()->updateValidity($id, $validity);
    }

    /** Transacción explícita para las escrituras auditadas (M6): se confirma solo después de registrar el evento. */
    public function begin(): void
    {
        $this->guard(fn () => $this->pdo()->beginTransaction());
    }

    public function commit(): void
    {
        $this->guard(fn () => $this->pdo()->commit());
    }

    public function rollBack(): void
    {
        try {
            if ($this->pdo !== null && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        } catch (Throwable) {
            // Si la conexión se perdió, PostgreSQL ya descartó la transacción.
        }
    }

    private function adapter(): PdoMedicationCatalog
    {
        return $this->adapter ??= $this->guard(fn () => new PdoMedicationCatalog($this->pdo()));
    }

    private function pdo(): PDO
    {
        return $this->pdo ??= ($this->connect)();
    }

    /** @template T @param Closure(): T $operation @return T */
    private function guard(Closure $operation): mixed
    {
        try {
            return $operation();
        } catch (Throwable $error) {
            throw new PersistenceFailure('La base HOSPITAL no está disponible.', previous: $error);
        }
    }
}
