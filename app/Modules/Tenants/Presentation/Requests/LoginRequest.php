<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use Psr\Http\Message\ServerRequestInterface;
final class LoginRequest
{
    private Email $email;
    private string $password;
    private TenantId $tenantId;
    private DeviceFingerprint $device;
    private string $ip;
    private function __construct(
        Email $email,
        string $password,
        TenantId $tenantId,
        DeviceFingerprint $device,
        string $ip
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->tenantId = $tenantId;
        $this->device = $device;
        $this->ip = $ip;
    }
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = $request->getParsedBody();
        $tenantHeader = $request->getHeaderLine('X-Tenant-ID');
        if (empty($tenantHeader)) {
            throw new \InvalidArgumentException('X-Tenant-ID header requerido');
        }
        $deviceHeader = $request->getHeaderLine('X-Device-ID');
        $deviceId = $deviceHeader ?: 'unknown_' . bin2hex(random_bytes(8));
        $userAgent = $request->getHeaderLine('User-Agent');
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        return new self(
            new Email($data['email'] ?? ''),
            $data['password'] ?? '',
            new TenantId($tenantHeader),
            \App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint::createNew($deviceId, $ip, $userAgent),
            $ip
        );
    }
    public function email(): Email { return $this->email; }
    public function password(): string { return $this->password; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function ip(): string { return $this->ip; }
}
