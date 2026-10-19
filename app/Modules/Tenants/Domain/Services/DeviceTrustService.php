<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
final class DeviceTrustService
{
    private SessionRepositoryInterface $sessionRepository;
    public function __construct(SessionRepositoryInterface $sessionRepository)
    {
        $this->sessionRepository = $sessionRepository;}
    public function registerDevice(UserId $userId, DeviceFingerprint $device): DeviceTrustResult
    {
        $existingSession = $this->sessionRepository->findByUserIdAndDevice($userId, $device->deviceId());
        if ($existingSession !== null) {
            $trustScore = min(100, $existingSession->device()->trustScore() + 10);
            $updatedDevice = $device->withTrustScore($trustScore);
            return new DeviceTrustResult($updatedDevice, true, 'Dispositivo conocido');}
        return new DeviceTrustResult($device, false, 'Dispositivo nuevo');}
    public function evaluateTrust(DeviceFingerprint $device): TrustLevel
    {
        if (!$device->isKnown()) {return TrustLevel::UNKNOWN;}
        if ($device->trustScore() >= 80) {return TrustLevel::TRUSTED;}
        if ($device->trustScore() >= 50) {return TrustLevel::LOW_TRUST;}
        return TrustLevel::SUSPICIOUS;}}
