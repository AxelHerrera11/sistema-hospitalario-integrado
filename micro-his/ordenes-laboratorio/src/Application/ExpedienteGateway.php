<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

/**
 * Puerto hacia el expediente y el catálogo de pruebas (módulos de otros dueños).
 * En la semana 3 se satisface con un adaptador en memoria; el adaptador real se conecta cuando
 * esos módulos publiquen su contrato.
 */
interface ExpedienteGateway
{
    /**
     * Contexto mínimo del expediente para emitir la orden.
     *
     * @return array{patient_id: string, episode_id: string, open: bool, clinical_note_ids: list<string>}
     */
    public function episodeContext(string $tenant, string $patientId, string $episodeId): array;

    /**
     * Pruebas activas y vigentes del catálogo.
     *
     * @param list<string> $testIds
     * @return array<string, array{code: string, name: string}>
     */
    public function activeTests(string $tenant, array $testIds): array;
}
