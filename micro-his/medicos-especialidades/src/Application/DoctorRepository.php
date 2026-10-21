<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

/**
 * Repository del agregado Médico: une los cuatro puertos que salieron de ISP (Semana 2).
 * Los adaptadores implementan este contrato completo; cada caso de uso sigue recibiendo solo el puerto que usa.
 */
interface DoctorRepository extends DoctorProfileStore, DoctorSpecialtyStore, SpecialtyCatalog, DoctorsBySpecialty
{
}
