<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/**
 * Aislamiento por tenant: entrega los almacenes del hospital indicado y de ningún otro. Un hospital sin conexión
 * configurada devuelve null; los casos de uso responden 503 sin revelar nada más.
 */
interface HospitalStoresProvider
{
    /** @throws PersistenceFailure si la conexión del hospital existe pero falla al abrirse */
    public function forHospital(string $hospitalId): ?HospitalStores;
}
