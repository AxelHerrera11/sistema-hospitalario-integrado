<?php

declare(strict_types=1);

namespace Reception\Application;

use Reception\Domain\CodeCollision;
use Reception\Domain\ReceptionError;
use Reception\Domain\SampleValidator;
use Reception\Domain\SpecimenCode;
use Reception\Domain\Uuid;

final class ReceiveSpecimen
{
    public function __construct(private ReceptionRepository $repository, private OrderGateway $orders, private ?\Closure $generate = null) {}

    public function execute(string $tenant, string $actor, array $input, \DateTimeImmutable $now): array
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->receive($tenant, $actor, $input, $now);
            } catch (CodeCollision $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }
        throw new \LogicException('Recepción sin resultado');
    }

    private function receive(string $tenant, string $actor, array $input, \DateTimeImmutable $now): array
    {
        return $this->repository->transaction(function () use ($tenant, $actor, $input, $now) {
            ksort($input);
            $hash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
            $previous = $this->repository->byRequest($tenant, $input['request_key']);
            if ($previous) {
                if ($previous['request_hash'] !== $hash || $previous['actor_id'] !== $actor) {
                    throw new ReceptionError('REQUEST_CONFLICT', 'Clave de solicitud ya utilizada', 409);
                }

                return $previous;
            }
            $order = $this->orders->order($tenant, $input['item_id']);
            if (! $order) {
                throw new ReceptionError('ORDER_NOT_FOUND', 'Ítem no encontrado', 404);
            }
            if (! in_array($order['status'], ['pending', 'pending_new_sample'], true)) {
                throw new ReceptionError('ORDER_CONFLICT', 'Ítem no disponible', 409);
            }
            $reason = ($input['sin_etiqueta'] ?? false) ? 'sin_etiqueta' :
                (($input['patient_code'] !== $order['patient_code'] || trim($input['patient_name']) !== $order['patient_name'])
                    ? 'identificacion_discordante' : null);
            $reason ??= (new SampleValidator($order['type']))->reason($input, $order, $now);
            $sample = [
                'id' => Uuid::generate(),
                'item_id' => $input['item_id'], 'actor_id' => $actor,
                'local_patient_id' => $order['local_patient_id'] ?? null, 'care_episode_id' => $order['care_episode_id'] ?? null,
                'request_key' => $input['request_key'], 'request_hash' => $hash,
                'volume' => $input['volume'], 'temperature' => $input['temperature'],
                'status' => $reason ? 'rejected' : 'accepted', 'reason' => $reason,
                'code' => $reason ? null : ($this->generate ? ($this->generate)() : SpecimenCode::generate()),
                'received_at' => $now->format(DATE_ATOM),
            ];
            $this->repository->save($sample);

            return $sample;
        });
    }
}
