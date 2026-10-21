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

    private function adapter(): PdoMedicationCatalog
    {
        try {
            return $this->adapter ??= new PdoMedicationCatalog(($this->connect)());
        } catch (Throwable $error) {
            throw new PersistenceFailure('La base HOSPITAL no está disponible.', previous: $error);
        }
    }
}
