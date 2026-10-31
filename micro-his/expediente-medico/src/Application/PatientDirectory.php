<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

use MicroHis\ExpedienteMedico\Domain\PatientSummary;

/** Puerto de lectura de pacientes. Adaptador real: Integration\PacientesDirectory sobre el PatientRepository de Pacientes. */
interface PatientDirectory
{
    /** @throws PersistenceFailure si el almacén no responde */
    public function findByCode(string $code): ?PatientSummary;
}
