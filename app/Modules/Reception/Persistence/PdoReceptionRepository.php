<?php

declare(strict_types=1);

namespace App\Modules\Reception\Persistence;

use PDO;
use App\Modules\Reception\Application\ReceptionRepository;
use App\Modules\Reception\Domain\CodeCollision;
use App\Modules\Reception\Domain\ReceptionError;

class PdoReceptionRepository implements ReceptionRepository
{
    public function __construct(protected PDO $pdo) {}

    public function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function save(array $sample): void
    {
        $statement = $this->pdo->prepare('INSERT INTO specimens
            (specimen_id,lab_order_item_id,received_by_user_ref_id,local_patient_id,care_episode_id,
             request_key,request_hash,volume,temperature,status,reason,code,received_at)
            VALUES (:id,:item_id,:actor_id,:local_patient_id,:care_episode_id,
             :request_key,:request_hash,:volume,:temperature,:status,:reason,:code,:received_at)');
        try {
            $statement->execute($sample);
        } catch (\PDOException $error) {
            if ($error->getCode() === '23505' && str_contains($error->getMessage(), 'specimens_code_unique')) {
                throw new CodeCollision('Código duplicado', 0, $error);
            }
            if ($error->getCode() === '23505') {
                throw new ReceptionError('REQUEST_CONFLICT', 'Solicitud o ítem ya recibido', 409);
            }
            throw $error;
        }
        $this->event($sample, $sample['id']);
    }

    protected function event(array $sample, string $eventId): void
    {
        $payload = array_intersect_key($sample, array_flip(['id', 'item_id', 'status', 'reason', 'code']));
        $statement = $this->pdo->prepare("INSERT INTO outbox_events
            (event_id,correlation_id,aggregate_type,aggregate_id,event_type,payload_minimal)
            VALUES (?,?,'specimen',?,?,?::jsonb)");
        $statement->execute([$eventId, $eventId, $sample['id'], 'specimen.'.$sample['status'], json_encode($payload, JSON_THROW_ON_ERROR)]);
    }

    public function find(string $tenant, string $code): ?array
    {
        return $this->lookup('code', $code);
    }

    public function byRequest(string $tenant, string $key): ?array
    {
        return $this->lookup('request_key', $key);
    }

    private function lookup(string $column, string $value): ?array
    {
        // $column solo procede de los dos métodos anteriores; la conexión ya está aislada por hospital.
        $statement = $this->pdo->prepare('SELECT specimen_id AS id,lab_order_item_id AS item_id,
            received_by_user_ref_id AS actor_id,local_patient_id,care_episode_id,request_key,request_hash,
            volume,temperature,status,reason,code,received_at FROM specimens WHERE '.$column.' = ?');
        $statement->execute([$value]);
        $sample = $statement->fetch(PDO::FETCH_ASSOC);
        if (! $sample) {
            return null;
        }
        $sample['received_at'] = (new \DateTimeImmutable($sample['received_at']))->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM);
        $sample['volume'] = (float) $sample['volume'];
        $sample['temperature'] = (float) $sample['temperature'];

        return $sample;
    }
}
