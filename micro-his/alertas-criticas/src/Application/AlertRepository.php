<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Application;

use MicroHis\Alertas\Domain\CriticalAlert;

/** Puerto de persistencia que necesita la aplicación; lo implementa Persistence. */
interface AlertRepository
{
    public function thresholds(string $labTestId): ?array; // {critical_low, critical_high} o null
    public function attending(string $episodeId): ?string;
    public function caresFor(string $user, string $patientId): bool;
    public function find(string $alertId): ?CriticalAlert;
    /** @return list<CriticalAlert> pendientes de acuse cuyo destinatario es el usuario, por vencimiento */
    public function pendingFor(string $user): array;
    /** Alerta (o acuse) y auditoría en una sola transacción. @throws PersistenceFailure duplicate = resultado repetido o ya acusada */
    public function save(CriticalAlert $alert, string $event, string $actor): void;
}
