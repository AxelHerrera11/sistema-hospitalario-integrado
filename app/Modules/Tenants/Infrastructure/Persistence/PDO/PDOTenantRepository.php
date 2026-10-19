<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\PDO;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use PDO;
final class PDOTenantRepository implements TenantRepositoryInterface
{
    private PDO $connection;
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;}
    public function save(Tenant $tenant): void
    {
        $sql = "
            INSERT INTO hospitals (hospital_id, name, status, user_quota, users_count, policies, created_at, updated_at)
            VALUES (:id, :name, :status, :quota, :count, :policies, :created, :updated)
            ON CONFLICT (hospital_id) DO UPDATE SET
                name = EXCLUDED.name,
                status = EXCLUDED.status,
                user_quota = EXCLUDED.user_quota,
                users_count = EXCLUDED.users_count,
                policies = EXCLUDED.policies,
                updated_at = EXCLUDED.updated_at
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'id' => $tenant->id()->value(),
            'name' => $tenant->name(),
            'status' => $tenant->status(),
            'quota' => $tenant->userQuota(),
            'count' => $tenant->usersCount(),
            'policies' => json_encode($tenant->policies()),
            'created' => $tenant->createdAt()->format('Y-m-d H:i:s.u'),
            'updated' => $tenant->updatedAt()->format('Y-m-d H:i:s.u'),]);}
    public function findById(TenantId $id): ?Tenant
    {
        $sql = "SELECT * FROM hospitals WHERE hospital_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['id' => $id->value()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;}
        return Tenant::reconstitute(
            TenantId::fromString($row['hospital_id']),
            $row['name'],
            $row['status'],
            (int)$row['user_quota'],
            (int)$row['users_count'],
            json_decode($row['policies'], true) ?? [],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at']));}
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM hospitals ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tenants = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tenants[] = Tenant::reconstitute(
                TenantId::fromString($row['hospital_id']),
                $row['name'],
                $row['status'],
                (int)$row['user_quota'],
                (int)$row['users_count'],
                json_decode($row['policies'], true) ?? [],
                new \DateTimeImmutable($row['created_at']),
                new \DateTimeImmutable($row['updated_at']));}
        return $tenants;}
    public function count(): int
    {
        $stmt = $this->connection->query('SELECT COUNT(*) FROM hospitals');
        return (int)$stmt->fetchColumn();}}
