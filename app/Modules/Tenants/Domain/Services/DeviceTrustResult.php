<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
final class DeviceTrustResult
{
    private DeviceFingerprint $device;
    private bool $isKnown;
    private string $message;
    public function __construct(DeviceFingerprint $device, bool $isKnown, string $message)
    {
        $this->device = $device;
        $this->isKnown = $isKnown;
        $this->message = $message;}
    public function device(): DeviceFingerprint { return $this->device; }
    public function isKnown(): bool { return $this->isKnown; }
    public function message(): string { return $this->message; }}
