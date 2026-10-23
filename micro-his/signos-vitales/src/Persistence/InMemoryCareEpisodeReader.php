<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Persistence;

use MicroHis\SignosVitales\Application\CareEpisodeReader;
use MicroHis\SignosVitales\Application\EpisodeContext;

/** Adaptador InMemory de la lectura de episodios: solo los médicos tratantes activos que se le indican. */
final class InMemoryCareEpisodeReader implements CareEpisodeReader
{
    /** @param array<string, array<string, EpisodeContext>> $contexts episodio => usuario con asignación activa => contexto */
    public function __construct(private readonly array $contexts = [])
    {
    }

    public function contextFor(string $userId, string $episodeId): ?EpisodeContext
    {
        return $this->contexts[$episodeId][$userId] ?? null;
    }
}
