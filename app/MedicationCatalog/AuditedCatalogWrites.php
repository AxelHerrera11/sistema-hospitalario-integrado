<?php

declare(strict_types=1);

namespace App\MedicationCatalog;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Closure;
use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Presentation\CatalogResponse;
use Throwable;

/**
 * Amenaza M6 (repudio): cada escritura confirmada deja un evento en la auditoría de CENTRAL con actor, hospital,
 * medicamento y trace_id. Falla cerrado: con el repositorio de HOSPITAL la escritura va en una transacción que solo se
 * confirma después de registrar el evento; si la auditoría falla, se revierte y se responde 503.
 */
final class AuditedCatalogWrites
{
    public function __construct(private readonly MedicationRepository $repository, private readonly AuditLogRepositoryInterface $audit)
    {
    }

    /**
     * @param Closure(): array{status: int, body: array} $write
     * @param array{user_id: ?string, hospital_id: ?string, trace_id: string} $actor
     * @return array{status: int, body: array}
     */
    public function run(string $event, array $actor, Closure $write): array
    {
        $transactional = $this->repository instanceof HospitalMedicationRepository ? $this->repository : null;
        try {
            $transactional?->begin();
        } catch (PersistenceFailure $error) {
            return CatalogResponse::failure($error);
        }
        $response = $write();
        if ($response['status'] >= 300) {
            $transactional?->rollBack();

            return $response;
        }
        $medication = $response['body']['medication'];
        try {
            $this->audit->logRaw($event, $actor + [
                'actor_id' => $actor['user_id'],
                'tenant_id' => $actor['hospital_id'],
                'medication_id' => (string) $medication['id'],
                'code' => $medication['code'],
                'validity' => $medication['validity'],
            ]);
        } catch (Throwable) {
            $transactional?->rollBack();

            return ['status' => 503, 'body' => ['error' => 'AUDIT_UNAVAILABLE', 'message' => 'No se pudo registrar la auditoría; no se confirmó la operación.']];
        }
        try {
            $transactional?->commit();
        } catch (PersistenceFailure $error) {
            return CatalogResponse::failure($error);
        }

        return $response;
    }
}
