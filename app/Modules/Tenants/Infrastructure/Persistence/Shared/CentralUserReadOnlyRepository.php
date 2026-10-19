<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\Shared;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use PDO;
final class CentralUserReadOnlyRepository implements UserRepositoryInterface
{
    private PDO $connection;
    public function __construct(PDO $centralConnection)
    {
        $this->connection = $centralConnection;}
    public function save(User $user): void
    {
        throw new \BadMethodCallException(
            'CentralUserReadOnlyRepository es de solo lectura. ' .
            'Use PDOUserRepository del módulo Tenants para escrituras.');}
    public function findById(UserId $id): ?User
    {
        $sql = "
            SELECT u.*, hm.role_code, hm.hospital_id
            FROM central_users u
            LEFT JOIN hospital_memberships hm ON u.user_id = hm.user_id
                AND hm.status = 'active'
                AND (hm.valid_to IS NULL OR hm.valid_to > NOW())
            WHERE u.user_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['id' => $id->value()]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return null;}
        return $this->mapRowsToUser($rows);}
    public function findByEmailAndTenant(Email $email, TenantId $tenantId): ?User
    {
        $sql = "
            SELECT u.*, hm.role_code, hm.hospital_id
            FROM central_users u
            JOIN hospital_memberships hm ON u.user_id = hm.user_id
            WHERE lower(u.email) = lower(:email)
            AND hm.hospital_id = :tenant_id
            AND hm.status = 'active'
            AND (hm.valid_to IS NULL OR hm.valid_to > NOW())
            AND u.status = 'active'";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'email' => $email->value(),
            'tenant_id' => $tenantId->value(),]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return null;}
        return $this->mapRowsToUser($rows);}
    public function findByIdAndTenant(UserId $id, TenantId $tenantId): ?User
    {
        $sql = "
            SELECT u.*, hm.role_code, hm.hospital_id
            FROM central_users u
            JOIN hospital_memberships hm ON u.user_id = hm.user_id
            WHERE u.user_id = :id
            AND hm.hospital_id = :tenant_id
            AND hm.status = 'active'
            AND (hm.valid_to IS NULL OR hm.valid_to > NOW())";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'id' => $id->value(),
            'tenant_id' => $tenantId->value(),]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return null;}
        return $this->mapRowsToUser($rows);}
    public function existsByEmailAndTenant(Email $email, TenantId $tenantId): bool
    {
        $sql = "
            SELECT 1 FROM central_users u
            JOIN hospital_memberships hm ON u.user_id = hm.user_id
            WHERE lower(u.email) = lower(:email)
            AND hm.hospital_id = :tenant_id
            AND hm.status = 'active'
            AND (hm.valid_to IS NULL OR hm.valid_to > NOW())";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'email' => $email->value(),
            'tenant_id' => $tenantId->value(),]);
        return $stmt->fetchColumn() !== false;}
    public function findAllByTenant(TenantId $tenantId, int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT u.*, hm.role_code, hm.hospital_id
            FROM central_users u
            JOIN hospital_memberships hm ON u.user_id = hm.user_id
            WHERE hm.hospital_id = :tenant_id
            AND hm.status = 'active'
            AND (hm.valid_to IS NULL OR hm.valid_to > NOW())
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':tenant_id', $tenantId->value());
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        $currentUserId = null;
        $currentRows = [];
        foreach ($rows as $row) {
            if ($currentUserId !== $row['user_id']) {
                if ($currentUserId !== null) {
                    $users[] = $this->mapRowsToUser($currentRows);}
                $currentUserId = $row['user_id'];
                $currentRows = [$row];} else {
                $currentRows[] = $row;}}
        if ($currentUserId !== null) {
            $users[] = $this->mapRowsToUser($currentRows);}
        return $users;}
    public function countByTenant(TenantId $tenantId): int
    {
        $sql = "
            SELECT COUNT(DISTINCT u.user_id)
            FROM central_users u
            JOIN hospital_memberships hm ON u.user_id = hm.user_id
            WHERE hm.hospital_id = :tenant_id
            AND hm.status = 'active'
            AND (hm.valid_to IS NULL OR hm.valid_to > NOW())";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId->value()]);
        return (int)$stmt->fetchColumn();}
    public function delete(UserId $id): void
    {
        throw new \BadMethodCallException(
            'CentralUserReadOnlyRepository es de solo lectura. ' .
            'Use PDOUserRepository del módulo Tenants para eliminaciones.');}
    private function mapRowsToUser(array $rows): User
    {
        $row = $rows[0];
        $roles = array_unique(array_column($rows, 'role_code'));
        $reflection = new \ReflectionClass(User::class);
        $user = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('id')->setValue($user, UserId::fromString($row['user_id']));
        $reflection->getProperty('email')->setValue($user, new Email($row['email']));
        $reflection->getProperty('password')->setValue($user, \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash($row['password_hash']));
        $reflection->getProperty('name')->setValue($user, $row['display_name']);
        $reflection->getProperty('roles')->setValue($user, array_map(fn(string $r) => new \App\Modules\Tenants\Domain\ValueObjects\Role($r), $roles));
        $reflection->getProperty('tenantId')->setValue($user, new TenantId($row['hospital_id']));
        $reflection->getProperty('status')->setValue($user, $row['status']);
        $reflection->getProperty('totpSecret')->setValue($user, $row['totp_secret']);
        $reflection->getProperty('mfaRequired')->setValue($user, (bool)$row['mfa_required']);
        $reflection->getProperty('createdAt')->setValue($user, new \DateTimeImmutable($row['created_at']));
        $reflection->getProperty('updatedAt')->setValue($user, new \DateTimeImmutable($row['updated_at']));
        $reflection->getProperty('lastLoginAt')->setValue($user, $row['last_login_at'] ? new \DateTimeImmutable($row['last_login_at']) : null);
        $reflection->getProperty('passwordChangedAt')->setValue($user, new \DateTimeImmutable($row['password_changed_at']));
        $reflection->getProperty('passwordHistory')->setValue($user, json_decode($row['password_history'], true) ?? []);
        return $user;
    }
}
