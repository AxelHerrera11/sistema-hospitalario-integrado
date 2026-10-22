<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Application;

use MicroHis\NativePhpPrototype\Domain\SyncPolicy;

final class SyncLocalActions
{
    public function __construct(
        private readonly LocalActionRepository $actions,
        private readonly SyncGateway $gateway,
        private readonly TelemetryRecorder $telemetry,
    ) {
    }

    /** @return array{synced:int, failed:int} */
    public function execute(SyncLocalActionsCommand $command): array
    {
        SyncPolicy::assertCanSync($command->connectivity);

        $synced = 0;
        $failed = 0;

        foreach ($this->actions->pendingForTenant($command->tenantId) as $action) {
            if ($this->gateway->push($action)) {
                $action->markSynced();
                $synced++;
            } else {
                $action->markFailed('Gateway simulado rechazo la accion.');
                $failed++;
            }

            $this->actions->update($action);
            $this->telemetry->record([
                'tenant_id' => $action->tenantId,
                'action_id' => $action->id,
                'event' => 'sync_attempt',
                'status' => $action->status->value,
            ]);
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
