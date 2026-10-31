<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Tests\Fixtures;

use MicroHis\OrdenesLab\Application\ExpedienteGateway;

/**
 * Doble del expediente y del catálogo para las pruebas de la semana 3.
 * El adaptador real se conectará cuando los módulos dueños publiquen su contrato.
 */
final class InMemoryExpedienteGateway implements ExpedienteGateway
{
    /**
     * @param array<string, array{patient_id: string, episode_id: string, open: bool, clinical_note_ids: list<string>}> $episodes
     * @param array<string, array<string, array{code: string, name: string}>> $tests
     */
    public function __construct(
        private readonly array $episodes,
        private readonly array $tests,
    ) {
    }

    public function episodeContext(string $tenant, string $patientId, string $episodeId): array
    {
        return $this->episodes["{$tenant}|{$patientId}|{$episodeId}"]
            ?? ['patient_id' => $patientId, 'episode_id' => $episodeId, 'open' => false, 'clinical_note_ids' => []];
    }

    public function activeTests(string $tenant, array $testIds): array
    {
        $active = [];
        foreach ($testIds as $testId) {
            if (isset($this->tests[$tenant][$testId])) {
                $active[$testId] = $this->tests[$tenant][$testId];
            }
        }

        return $active;
    }
}
