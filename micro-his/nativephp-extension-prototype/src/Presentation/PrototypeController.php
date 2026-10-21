<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Presentation;

use MicroHis\NativePhpPrototype\Application\PrepareLocalAction;
use MicroHis\NativePhpPrototype\Application\PrepareLocalActionCommand;
use MicroHis\NativePhpPrototype\Application\SyncLocalActions;
use MicroHis\NativePhpPrototype\Application\SyncLocalActionsCommand;
use MicroHis\NativePhpPrototype\Domain\ConnectivityStatus;
use MicroHis\NativePhpPrototype\Domain\DomainRuleViolation;
use MicroHis\NativePhpPrototype\Application\LocalActionNotFound;

final class PrototypeController
{
    public function __construct(
        private readonly PrepareLocalAction $prepareLocalAction,
        private readonly SyncLocalActions $syncLocalActions,
    ) {
    }

    /** @param array<string, string|null> $request */
    public function prepare(array $request): array
    {
        try {
            $response = $this->prepareLocalAction->execute(new PrepareLocalActionCommand(
                tenantId: (string) ($request['tenant_id'] ?? ''),
                actorId: (string) ($request['actor_id'] ?? ''),
                kind: (string) ($request['kind'] ?? ''),
                payload: (string) ($request['payload'] ?? ''),
            ));

            return ['status' => 200, 'body' => $response];
        } catch (DomainRuleViolation $error) {
            return ['status' => 422, 'body' => ['error' => $error->getMessage()]];
        } catch (LocalActionNotFound $error) {
            return ['status' => 404, 'body' => ['error' => $error->getMessage()]];
        } catch (\Throwable) {
            return ['status' => 500, 'body' => ['error' => 'No fue posible preparar la accion local.']];
        }
    }

    /** @param array<string, string|null> $request */
    public function sync(array $request): array
    {
        try {
            $response = $this->syncLocalActions->execute(new SyncLocalActionsCommand(
                tenantId: (string) ($request['tenant_id'] ?? ''),
                connectivity: ConnectivityStatus::from((string) ($request['connectivity'] ?? 'offline')),
            ));

            return ['status' => 200, 'body' => $response];
        } catch (DomainRuleViolation $error) {
            return ['status' => 422, 'body' => ['error' => $error->getMessage()]];
        } catch (\Throwable) {
            return ['status' => 500, 'body' => ['error' => 'No fue posible sincronizar.']];
        }
    }
}
