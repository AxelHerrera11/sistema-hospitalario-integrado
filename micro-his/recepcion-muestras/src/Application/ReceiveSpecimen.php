<?php
declare(strict_types=1);
namespace Reception\Application;

use Reception\Domain\SampleValidator;
use Reception\Domain\SpecimenCode;

final class ReceiveSpecimen
{
    public function __construct(private ReceptionRepository $repository) {}

    public function execute(string $tenant, string $actor, array $input, \DateTimeImmutable $now): array
    {
        return $this->repository->transaction(function () use ($tenant, $actor, $input, $now) {
            $order = $this->repository->order($tenant, $input['item_id']);
            if (!$order) throw new \DomainException('Ítem no encontrado', 404);
            if (!in_array($order['status'], ['pending', 'pending_new_sample'], true)) {
                throw new \DomainException('Ítem no disponible', 409);
            }
            $reason = ($input['patient_code'] !== $order['patient_code']
                || trim($input['patient_name']) !== $order['patient_name']) ? 'identificacion_discordante' : null;
            $reason ??= (new SampleValidator($order['type']))->reason($input, $order, $now);
            $sample = [
                'id' => bin2hex(random_bytes(16)), 'tenant_id' => $tenant,
                'item_id' => $input['item_id'], 'actor_id' => $actor,
                'status' => $reason ? 'rejected' : 'accepted', 'reason' => $reason,
                'code' => $reason ? null : SpecimenCode::generate(), 'received_at' => $now->format(DATE_ATOM),
            ];
            $this->repository->save($sample);
            return $sample;
        });
    }
}
