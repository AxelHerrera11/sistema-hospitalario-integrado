<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
/** Puerto de emisión y validación de access tokens; la firma vive en Infrastructure\Security. */
interface TokenFactory
{
    public function createAccessToken(User $user, Tenant $tenant, SessionId $sessionId, DeviceFingerprint $device): AccessToken;
    /** @throws \InvalidArgumentException si el token es inválido, está mal firmado o venció. */
    public function validateAccessToken(string $token): JwtClaims;
    public function accessTokenTtl(): int;}
