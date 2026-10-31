<?php

declare(strict_types=1);

namespace App\Prescripciones;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use MicroHis\MedicationCatalog\Application\MedicationLookup;
use MicroHis\MedicationCatalog\Application\PersistenceFailure as CatalogPersistenceFailure;
use MicroHis\MedicationCatalog\Domain\Validity;
use MicroHis\Prescripciones\Application\AuditLogger;
use MicroHis\Prescripciones\Application\Exception\AuditUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\MedicationCatalog;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use Throwable;
final readonly class PrescriptionsAdapters implements AuditLogger, MedicationCatalog
{
    public function __construct(
        private MedicationLookup $medications,
        private AuditLogRepositoryInterface $audit,
    ) {}
    public function find(string $medicationId): MedicationSnapshot
    {
        try {
            $medication = $this->medications->findById($medicationId);
        } catch (CatalogPersistenceFailure $error) {
            throw new PersistenceFailure('No se pudo consultar el catálogo de medicamentos.', $error);
        }
        if ($medication === null || $medication->id === null || $medication->validity !== Validity::Active) {
            throw new MedicationNotFound($medicationId);
        }
        return new MedicationSnapshot($medication->id, $medication->code, $medication->name, $medication->activeIngredient, $medication->pharmacologicalGroup, true);
    }
    public function record(string $eventName, array $payload): void
    {
        try {
            $this->audit->logRaw($eventName, $payload);
        } catch (Throwable $error) {
            throw new AuditUnavailable(previous: $error);
        }
    }
}