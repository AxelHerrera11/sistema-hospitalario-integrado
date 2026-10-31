<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
final class LoginRequest
{
    private function __construct(
        private Email $email,
        private string $password,
        private TenantId $tenantId,
        private DeviceFingerprint $device,
        private string $ip
    ) {}
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = (array) $request->getParsedBody();
        $tenantHeader = $request->getHeaderLine('X-Tenant-ID');
        if ($tenantHeader === '') {
            throw new \InvalidArgumentException('X-Tenant-ID header requerido');}
        if (!is_string($data['password'] ?? null) || $data['password'] === '') {
            throw new \InvalidArgumentException('La contraseña es obligatoria');}
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $deviceId = $request->getHeaderLine('X-Device-ID') ?: Uuid::uuid4()->toString();
        $userAgent = $request->getHeaderLine('User-Agent') ?: 'unknown';
        return new self(
            new Email((string) ($data['email'] ?? '')),
            $data['password'],
            new TenantId($tenantHeader),
            DeviceFingerprint::createNew($deviceId, $ip, $userAgent),
            $ip);}
    public function email(): Email { return $this->email; }
    public function password(): string { return $this->password; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function ip(): string { return $this->ip; }}
