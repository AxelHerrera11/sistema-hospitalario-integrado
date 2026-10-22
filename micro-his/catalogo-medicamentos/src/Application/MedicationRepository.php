<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

/**
 * Repository del agregado Medicamento: contrato completo que debe cumplir cada adaptador de Persistence.
 * Une los tres puertos segregados; cada caso de uso sigue recibiendo solo el suyo (ISP de Semana 2).
 */
interface MedicationRepository extends MedicationRegistry, MedicationReader, MedicationValidityStore
{
}
