<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Domain;

/**
 * Vista longitudinal: expediente + paciente + episodios ordenados del más reciente al más antiguo.
 * Regla de integridad: todo pertenece al mismo paciente; si no, no se muestra nada (500, sin datos).
 */
final readonly class LongitudinalRecord
{
    /** @var list<CareEpisode> */
    public array $episodes;

    /** @param list<CareEpisode> $episodes */
    public function __construct(public MedicalRecord $record, public PatientSummary $patient, array $episodes)
    {
        if ($record->patientId !== $patient->id) {
            throw new ExpedienteRuleViolation('EXPEDIENTE_DE_OTRO_PACIENTE', 500);
        }
        foreach ($episodes as $episode) {
            if (!$episode instanceof CareEpisode || $episode->patientId !== $patient->id) {
                throw new ExpedienteRuleViolation('EPISODIO_DE_OTRO_PACIENTE', 500);
            }
        }
        usort($episodes, static fn (CareEpisode $a, CareEpisode $b): int => [$b->startedAt, $b->id] <=> [$a->startedAt, $a->id]);
        $this->episodes = $episodes;
    }

    public function openEpisodes(): int
    {
        return count(array_filter($this->episodes, static fn (CareEpisode $e): bool => $e->isOpen()));
    }
}
