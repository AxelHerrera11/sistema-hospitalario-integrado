<?php

namespace Tests\Unit\Tenants\Domain\Services;

use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryRefreshTokenRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Semana 15 · UT-003 y WB-002: rotación de refresh tokens de un solo uso, recorriendo cada
 * camino de rotate() con el repositorio en memoria real.
 */
#[Group('unitarias')]
class RefreshTokenRotatorTest extends TestCase
{
    private InMemoryRefreshTokenRepository $repository;

    private RefreshTokenRotator $rotator;

    protected function setUp(): void
    {
        $this->repository = new InMemoryRefreshTokenRepository();
        $this->rotator = new RefreshTokenRotator($this->repository, 3600);
    }

    public function test_issue_stores_only_the_hash_of_the_secret(): void
    {
        $issued = $this->issue();
        [$id, $secret] = RefreshToken::parse($issued->token());
        $stored = $this->repository->findById($id);

        $this->assertNotNull($stored);
        $this->assertTrue($stored->matches($secret));
        $this->assertStringNotContainsString($secret, json_encode($stored->toArray()));
        $this->assertGreaterThan(time(), $issued->expiresAt());
    }

    #[Group('caja-blanca')]
    public function test_path_valid_token_rotates_and_old_one_is_marked(): void
    {
        $issued = $this->issue();

        $result = $this->rotator->rotate($issued->token());

        $this->assertTrue($result->isSuccess());
        $this->assertNotSame($issued->token(), $result->next()->token());
        $this->assertTrue($result->previous()->isRotated());
        $this->assertTrue($this->rotator->rotate($result->next()->token())->isSuccess());
    }

    #[Group('caja-blanca')]
    public function test_path_rotated_token_presented_again_is_reuse(): void
    {
        $issued = $this->issue();
        $this->rotator->rotate($issued->token());

        $result = $this->rotator->rotate($issued->token());

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isReuseDetected());
        $this->assertSame($issued->token(), $result->previous()->id() . '.' . RefreshToken::parse($issued->token())[1]);
    }

    #[Group('caja-blanca')]
    public function test_path_unknown_or_malformed_token_fails_without_reuse(): void
    {
        foreach (['no-es-un-token', '9b2f6a2e-1c1d-4f5e-9a7b-3c2d1e0f4a5b.secreto', ''] as $raw) {
            $result = $this->rotator->rotate($raw);
            $this->assertFalse($result->isSuccess());
            $this->assertFalse($result->isReuseDetected(), $raw);
        }
    }

    #[Group('caja-blanca')]
    public function test_path_wrong_secret_fails_without_revealing_reuse(): void
    {
        [$id] = RefreshToken::parse($this->issue()->token());

        $result = $this->rotator->rotate($id . '.' . str_repeat('f', 64));

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isReuseDetected());
    }

    #[Group('caja-blanca')]
    public function test_path_revoked_and_expired_tokens_fail(): void
    {
        $revoked = $this->issue();
        $this->repository->findById(RefreshToken::parse($revoked->token())[0])->revoke();
        // TTL 0: vence en el mismo instante en que se emite (isExpired usa >=).
        $expired = (new RefreshTokenRotator($this->repository, 0))->issue(TenantId::generate(), UserId::generate(), SessionId::generate(), $this->device());

        $this->assertFalse($this->rotator->rotate($revoked->token())->isSuccess());
        $this->assertFalse($this->rotator->rotate($expired->token())->isSuccess());
    }

    private function issue(): \App\Modules\Tenants\Domain\Services\RefreshTokenResult
    {
        return $this->rotator->issue(TenantId::generate(), UserId::generate(), SessionId::generate(), $this->device());
    }

    private function device(): DeviceFingerprint
    {
        return DeviceFingerprint::createNew('equipo-01', '10.0.0.1', 'PHPUnit');
    }
}
