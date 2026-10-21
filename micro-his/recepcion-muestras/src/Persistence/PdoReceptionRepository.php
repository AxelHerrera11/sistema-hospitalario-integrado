<?php
declare(strict_types=1);
namespace Reception\Persistence;

use PDO;
use Reception\Application\ReceptionRepository;

final class PdoReceptionRepository implements ReceptionRepository
{
    public function __construct(private PDO $pdo) {}

    public function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $operation();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function order(string $tenant, string $item): ?array
    {
        $lock = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare('SELECT * FROM reception_order_items WHERE tenant_id = ? AND item_id = ?'.$lock);
        $stmt->execute([$tenant, $item]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function save(array $sample): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO reception_specimens
            (id, tenant_id, item_id, actor_id, status, reason, code, received_at)
            VALUES (:id, :tenant_id, :item_id, :actor_id, :status, :reason, :code, :received_at)');
        $stmt->execute($sample);
        $stmt = $this->pdo->prepare('UPDATE reception_order_items SET status = ? WHERE tenant_id = ? AND item_id = ?');
        $stmt->execute([$sample['status'] === 'accepted' ? 'received' : 'pending_new_sample', $sample['tenant_id'], $sample['item_id']]);
        if ($sample['status'] === 'rejected') {
            $stmt = $this->pdo->prepare('INSERT INTO reception_outbox (specimen_id, tenant_id, payload) VALUES (?, ?, ?)');
            $stmt->execute([$sample['id'], $sample['tenant_id'], json_encode($sample, JSON_THROW_ON_ERROR)]);
        }
    }

    public function find(string $tenant, string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reception_specimens WHERE tenant_id = ? AND code = ?');
        $stmt->execute([$tenant, $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
