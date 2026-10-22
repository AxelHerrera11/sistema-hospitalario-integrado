<?php

declare(strict_types=1);

namespace App\Modules\Reception\Infrastructure;

use App\Modules\Reception\Application\ReceptionRepository;
use App\Modules\Reception\Domain\CodeCollision;
use Illuminate\Support\Str;
use PDO;

final class PdoReceptionRepository implements ReceptionRepository
{
    private const COLUMNS = 'specimen_id AS id, tenant_id, lab_order_item_id AS item_id, actor_id, status, request_key, request_hash, reason, code, received_at';

    public function __construct(private PDO $pdo) {}

    public function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            } throw $e;
        }
    }

    private function lock(string $key): void
    {
        $this->pdo->prepare('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))')->execute([$key]);
    }

    public function order(string $tenant, string $item): ?array
    {
        $this->lock($tenant.':item:'.$item);
        $accepted = $this->pdo->prepare("SELECT 1 FROM specimens WHERE tenant_id = ? AND lab_order_item_id = ? AND status = 'accepted'");
        $accepted->execute([$tenant, $item]);
        if ($accepted->fetchColumn()) {
            throw new \DomainException('Ítem ya recibido', 409);
        }
        $order = (new OrdersGateway)->get($tenant, $item)[0] ?? null;
        if (! $order) {
            return null;
        }
        $patient = $this->pdo->prepare("SELECT 1 FROM local_patients WHERE local_patient_id = ? AND code = ? AND concat_ws(' ', given_name, family_name) = ?");
        $patient->execute([$order['local_patient_id'], $order['patient_code'], $order['patient_name']]);
        if (! $patient->fetchColumn()) {
            throw new \DomainException('Identidad de orden no coincide con paciente local', 503);
        }

        return $order;
    }

    public function save(array $sample): void
    {
        $actor = $this->pdo->prepare("SELECT local_user_ref_id FROM local_user_refs WHERE central_user_id = ? AND status = 'active' AND (valid_to IS NULL OR valid_to > now())");
        $actor->execute([$sample['actor_id']]);
        $sample['received_by_user_ref_id'] = $actor->fetchColumn();
        if (! $sample['received_by_user_ref_id']) {
            throw new \DomainException('Identidad local no sincronizada', 409);
        }
        if ($sample['care_episode_id']) {
            $episode = $this->pdo->prepare('SELECT 1 FROM care_episodes WHERE care_episode_id = ? AND local_patient_id = ?');
            $episode->execute([$sample['care_episode_id'], $sample['local_patient_id']]);
            if (! $episode->fetchColumn()) {
                throw new \DomainException('Episodio y paciente no coinciden', 409);
            }
        }
        $stmt = $this->pdo->prepare('INSERT INTO specimens
            (specimen_id, tenant_id, lab_order_item_id, actor_id, received_by_user_ref_id, local_patient_id, care_episode_id, request_key, request_hash, status, reason, code, received_at)
            VALUES (:id, :tenant_id, :item_id, :actor_id, :received_by_user_ref_id, :local_patient_id, :care_episode_id, :request_key, :request_hash, :status, :reason, :code, :received_at)');
        try {
            $stmt->execute($sample);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505' && str_contains($e->getMessage(), 'specimens_code_key')) {
                throw new CodeCollision('Colisión de identificador', 0, $e);
            }
            throw $e;
        }
        $payload = array_intersect_key($sample, array_flip(['id', 'tenant_id', 'item_id', 'status', 'reason', 'code', 'received_at']));
        $this->pdo->prepare('INSERT INTO outbox_events (event_id, correlation_id, aggregate_type, aggregate_id, event_type, payload_minimal, metadata)
            VALUES (?, ?, ?, ?, ?, ?::jsonb, ?::jsonb)')->execute([
            (string) Str::uuid(), $sample['request_key'], 'specimen', $sample['id'], 'lab.specimen.'.$sample['status'],
            json_encode($payload, JSON_THROW_ON_ERROR), json_encode(['tenant_id' => $sample['tenant_id']], JSON_THROW_ON_ERROR),
        ]);
    }

    public function find(string $tenant, string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT '.self::COLUMNS.' FROM specimens WHERE tenant_id = ? AND code = ?');
        $stmt->execute([$tenant, $code]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function byRequest(string $tenant, string $key): ?array
    {
        $this->lock($tenant.':request:'.$key);
        $stmt = $this->pdo->prepare('SELECT '.self::COLUMNS.' FROM specimens WHERE tenant_id = ? AND request_key = ?');
        $stmt->execute([$tenant, $key]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
