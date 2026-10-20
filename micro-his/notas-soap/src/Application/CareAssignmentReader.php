<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

/** Lectura de care_assignments: ¿el usuario es médico tratante activo del episodio? */
interface CareAssignmentReader
{
    public function isTreating(string $userId, string $episodeId): bool;
}
