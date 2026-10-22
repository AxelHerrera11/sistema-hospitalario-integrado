<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\CareAssignmentReader;

/** Doble de Episodios: asignaciones activas en memoria. */
final class InMemoryCareAssignmentReader implements CareAssignmentReader
{
    /** @param array<string, list<string>> $active id del episodio => usuarios con asignación activa */
    public function __construct(private readonly array $active = [])
    {
    }

    public function isTreating(string $userId, string $episodeId): bool
    {
        return in_array($userId, $this->active[$episodeId] ?? [], true);
    }
}
