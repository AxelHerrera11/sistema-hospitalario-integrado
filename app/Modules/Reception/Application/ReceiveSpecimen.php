<?php
declare(strict_types=1);
namespace App\Modules\Reception\Application;

use App\Modules\Reception\Domain\SampleValidator;
use App\Modules\Reception\Domain\SpecimenCode;
use App\Modules\Reception\Domain\CodeCollision;

final class ReceiveSpecimen
{
    public function __construct(private ReceptionRepository $repository, private ?\Closure $generate = null) {}

    public function execute(string $tenant, string $actor, array $input, \DateTimeImmutable $now): array
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->receive($tenant, $actor, $input, $now);
            } catch (CodeCollision $e) {
                if ($attempt === 2) throw $e;
            }
        }
        throw new \LogicException('Recepción sin resultado');
    }

    private function receive(string $tenant, string $actor, array $input, \DateTimeImmutable $now): array
    {
        return $this->repository->transaction(function () use ($tenant, $actor, $input, $now) {
            $order = $this->repository->order($tenant, $input['item_id']);
            if (!$order) throw new \DomainException('Ítem no encontrado', 404);
            ksort($input);
            $hash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
            $previous = $this->repository->byRequest($tenant, $input['request_key']);
            if ($previous) {
                if ($previous['request_hash'] !== $hash || $previous['actor_id'] !== $actor) {
                    throw new \DomainException('Clave de solicitud ya utilizada', 409);
                }
                return $previous;
            }
            if (!in_array($order['status'], ['pending', 'pending_new_sample'], true)) {
                throw new \DomainException('Ítem no disponible', 409);
            }
            $reason = ($input['patient_code'] !== $order['patient_code']
                || trim($input['patient_name']) !== $order['patient_name']) ? 'identificacion_discordante' : null;
            $reason ??= (new SampleValidator($order['type']))->reason($input, $order, $now);
            $sample = [
                'id' => bin2hex(random_bytes(16)), 'tenant_id' => $tenant,
                'item_id' => $input['item_id'], 'actor_id' => $actor,
                'request_key' => $input['request_key'], 'request_hash' => $hash,
                'status' => $reason ? 'rejected' : 'accepted', 'reason' => $reason,
                'code' => $reason ? null : ($this->generate ? ($this->generate)() : SpecimenCode::generate()),
                'received_at' => $now->format(DATE_ATOM),
            ];
            $this->repository->save($sample);
            return $sample;
        });
    }
}
