<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Persistence;

use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Application\HospitalStoresProvider;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;

/**
 * Registro de hospitales: cada tenant tiene su fábrica de almacenes (su propia base HOSPITAL). Las fábricas son
 * perezosas: la caída de la base de un hospital no afecta a otro, y nunca se entrega la base de un tenant a otro.
 */
final class HospitalRegistry implements HospitalStoresProvider
{
    /** @var array<string, HospitalStores> */
    private array $opened = [];

    /** @param array<string, \Closure(): HospitalStores> $factories */
    public function __construct(private readonly array $factories)
    {
    }

    public function forHospital(string $hospitalId): ?HospitalStores
    {
        if (!isset($this->factories[$hospitalId])) {
            return null;
        }
        try {
            return $this->opened[$hospitalId] ??= ($this->factories[$hospitalId])();
        } catch (\PDOException) {
            throw new PersistenceFailure('hospital_connection_failed');
        }
    }
}
