<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Application;

use MicroHis\NativePhpPrototype\Domain\LocalHisAction;

final class PrepareLocalAction
{
    public function __construct(
        private readonly LocalActionRepository $actions,
        private readonly TelemetryRecorder $telemetry,
    ) {
    }

    /** @return array<string, string> */
    public function execute(PrepareLocalActionCommand $command): array
    {
        $action = new LocalHisAction(
            id: 'local-' . bin2hex(random_bytes(4)),
            tenantId: $command->tenantId,
            actorId: $command->actorId,
            kind: $command->kind,
            payload: $command->payload,
        );

        $this->actions->save($action);
        $this->telemetry->record([
            'tenant_id' => $action->tenantId,
            'action_id' => $action->id,
            'event' => 'prepared',
            'status' => $action->status->value,
        ]);

        return ['id' => $action->id, 'status' => $action->status->value];
    }
}
