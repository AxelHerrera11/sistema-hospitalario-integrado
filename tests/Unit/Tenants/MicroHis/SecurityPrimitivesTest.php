<?php

namespace Tests\Unit\Tenants\MicroHis;

use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Password;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryAuditLogRepository;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryRefreshTokenRepository;
use App\Modules\Tenants\Infrastructure\Security\JwtTokenService;
use App\Modules\Tenants\Infrastructure\Security\TotpVerifier;
use DateTimeImmutable;
use InvalidArgumentException;
use Lcobucci\Clock\FrozenClock;
use PHPUnit\Framework\TestCase;

/**
 * Piezas de seguridad del micro-HIS de Semana 3, sin Laravel: JWT, TOTP,
 * refresh de un solo uso, roles canónicos, auditoría y autoload PSR-4.
 */
class SecurityPrimitivesTest extends TestCase
{
    private const SECRET = 'clave-de-prueba-de-al-menos-32-bytes!!';

    public function test_jwt_round_trip_keeps_tenant_session_and_roles(): void
    {
        [$user, $tenant] = $this->userAndTenant();
        $service = new JwtTokenService(self::SECRET);
        $sessionId = SessionId::generate();

        $token = $service->createAccessToken($user, $tenant, $sessionId, $this->device());
        $claims = $service->validateAccessToken($token->token());

        $this->assertSame($user->id()->value(), $claims->subject());
        $this->assertSame($tenant->id()->value(), $claims->tenantId());
        $this->assertSame($sessionId->value(), $claims->sessionId());
        $this->assertSame(['medico'], $claims->roles());
        $this->assertSame($token->jti(), $claims->jti());
    }

    public function test_jwt_rejects_tampered_foreign_and_expired_tokens(): void
    {
        [$user, $tenant] = $this->userAndTenant();
        $service = new JwtTokenService(self::SECRET);
        $token = $service->createAccessToken($user, $tenant, SessionId::generate(), $this->device())->token();

        $rejected = [
            'firma alterada' => [$service, substr($token, 0, -2) . (str_ends_with($token, 'AA') ? 'BB' : 'AA')],
            'otro secreto' => [new JwtTokenService(str_repeat('x', 40)), $token],
            'otra audiencia' => [new JwtTokenService(self::SECRET, audience: 'otra-api'), $token],
            'vencido' => [$service, (new JwtTokenService(self::SECRET, clock: new FrozenClock(new DateTimeImmutable('-1 hour'))))->createAccessToken($user, $tenant, SessionId::generate(), $this->device())->token()],
            'basura' => [$service, 'no-es-un-jwt'],
        ];
        foreach ($rejected as $case => [$validator, $candidate]) {
            try {
                $validator->validateAccessToken($candidate);
                $this->fail("Se aceptó un token con {$case}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_jwt_requires_a_strong_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtTokenService('corto');
    }

    public function test_totp_matches_rfc6238_reference_vectors(): void
    {
        // RFC 6238, apéndice B: clave ASCII "12345678901234567890" en Base32, SHA-1, 8 dígitos.
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $totp = new TotpVerifier(window: 0, digits: 8);

        $this->assertTrue($totp->verify($secret, '94287082', 59));
        $this->assertTrue($totp->verify($secret, '07081804', 1111111109));
        $this->assertTrue($totp->verify($secret, '89005924', 1234567890));
        $this->assertFalse($totp->verify($secret, '94287082', 1111111109));
        $this->assertFalse($totp->verify($secret, 'abcdefgh', 59));
    }

    public function test_generated_totp_secret_verifies_its_own_codes(): void
    {
        $totp = new TotpVerifier();
        $secret = $totp->generateSecret();
        $now = 1_800_000_000;

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertTrue($totp->verify($secret, $totp->codeAt($secret, intdiv($now, 30)), $now));
        $this->assertTrue($totp->verify($secret, $totp->codeAt($secret, intdiv($now, 30) - 1), $now), 'tolera un paso de desfase');
        $this->assertFalse($totp->verify($secret, $totp->codeAt($secret, intdiv($now, 30) - 3), $now));
    }

    public function test_refresh_token_is_single_use_and_never_persisted_in_clear(): void
    {
        $repository = new InMemoryRefreshTokenRepository();
        $rotator = new RefreshTokenRotator($repository, 3600);
        $issued = $rotator->issue(TenantId::generate(), $this->userAndTenant()[0]->id(), SessionId::generate(), $this->device());
        [$id, $secret] = RefreshToken::parse($issued->token());

        $persisted = json_encode($repository->findById($id)->toArray());
        $this->assertStringNotContainsString($secret, $persisted);
        $this->assertStringContainsString(hash('sha256', $secret), $persisted);

        $rotation = $rotator->rotate($issued->token());
        $this->assertTrue($rotation->isSuccess());
        $this->assertNotSame($issued->token(), $rotation->next()->token());
        $this->assertTrue($rotator->rotate($rotation->next()->token())->isSuccess(), 'el token nuevo sí rota');

        $replay = $rotator->rotate($issued->token());
        $this->assertTrue($replay->isReuseDetected());
        $this->assertFalse($rotator->rotate($id . '.' . str_repeat('0', 64))->isSuccess(), 'secreto incorrecto');
    }

    public function test_roles_are_exactly_the_canonical_rbac_codes(): void
    {
        $rbac = require dirname(__DIR__, 4) . '/config/rbac.php';

        $this->assertEqualsCanonicalizing(array_keys($rbac['roles']), Role::all());
        $this->assertSame('admin_hospital', (new Role('admin_hospital'))->value());
        $this->expectException(InvalidArgumentException::class);
        new Role('ADMIN_HOSPITAL');
    }

    public function test_audit_log_is_hash_chained_and_rejects_secrets(): void
    {
        $audit = new InMemoryAuditLogRepository();
        $audit->log(new DomainEvent(DomainEvent::LOGIN_FAILED, ['tenant_id' => 't1', 'reason' => 'bad_password']));
        $audit->log(new DomainEvent(DomainEvent::REFRESH_REUSE_DETECTED, ['tenant_id' => 't1']));

        $this->assertTrue($audit->verifyHashChain());
        $this->assertSame('CRITICAL', $audit->all()[1]['severity']);
        $this->assertSame($audit->all()[0]['hash'], $audit->all()[1]['previous_hash']);
        $this->expectException(InvalidArgumentException::class);
        new DomainEvent(DomainEvent::LOGIN_FAILED, ['password' => 'filtrada']);
    }

    public function test_every_module_class_autoloads_from_a_clean_process(): void
    {
        $root = dirname(__DIR__, 4);
        $script = <<<'PHP'
            <?php
            require $argv[1] . '/vendor/autoload.php';
            $base = realpath($argv[1] . '/app/Modules/Tenants') . DIRECTORY_SEPARATOR;
            $missing = [];
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)) as $file) {
                if ($file->getExtension() !== 'php' || $file->getFilename() === 'micro-his.php') continue;
                $class = 'App\\Modules\\Tenants\\' . strtr(substr($file->getPathname(), strlen($base), -4), ['/' => '\\']);
                if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) $missing[] = $class;
            }
            echo json_encode($missing);
            PHP;

        $file = tempnam(sys_get_temp_dir(), 'autoload') . '.php';
        file_put_contents($file, $script);
        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($root));
        unlink($file);

        $this->assertSame([], json_decode((string) $output, true), (string) $output);
    }

    /** @return array{0: User, 1: Tenant} */
    private function userAndTenant(): array
    {
        $tenant = Tenant::create(TenantId::generate(), 'Hospital Prueba');
        $password = Password::fromRaw('Clave-Segura-2026!', new Argon2idHasher(1024, 1, 1));
        $user = User::create(new Email('medico@prueba.test'), $password, 'Dra. Prueba', [new Role('medico')], $tenant->id());

        return [$user, $tenant];
    }

    private function device(): DeviceFingerprint
    {
        return DeviceFingerprint::createNew('equipo-01', '127.0.0.1', 'PHPUnit');
    }
}
