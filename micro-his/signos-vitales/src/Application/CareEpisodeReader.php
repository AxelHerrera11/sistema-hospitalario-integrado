<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

/** Lectura de care_episodes y care_assignments. */
interface CareEpisodeReader
{
    /** @return EpisodeContext|null null si el usuario no es médico tratante activo del episodio */
    public function contextFor(string $userId, string $episodeId): ?EpisodeContext;
}
