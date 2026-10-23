<?php

namespace Tests\Unit\Tenants\Domain\Services;

use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\CompromisedPasswordChecker;
use App\Modules\Tenants\Domain\Services\PasswordPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Semana 15 · UT-001 y WB-001: política de contraseñas. El verificador de filtraciones es un
 * fake del puerto CompromisedPasswordChecker (no un doble de una clase final).
 */
#[Group('unitarias')]
class PasswordPolicyTest extends TestCase
{
    private Argon2idHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new Argon2idHasher(1024, 1, 1);
    }

    public function test_strong_password_passes(): void
    {
        $result = (new PasswordPolicy($this->hasher))->validate('Clave-Segura-2026!');

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->errors());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function ramasDeComplejidad(): array
    {
        return [
            'menos de 12 caracteres' => ['Corta-1a', 'al menos 12 caracteres'],
            'sin mayúscula' => ['clave-segura-2026!', 'una mayúscula'],
            'sin minúscula' => ['CLAVE-SEGURA-2026!', 'una minúscula'],
            'sin número' => ['Clave-Segura-Dos!', 'un número'],
            'sin símbolo' => ['ClaveSegura2026', 'un símbolo'],
        ];
    }

    #[DataProvider('ramasDeComplejidad')]
    #[Group('caja-blanca')]
    public function test_each_complexity_branch_reports_its_error(string $password, string $expected): void
    {
        $result = (new PasswordPolicy($this->hasher))->validate($password);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString($expected, (string) $result->firstError());
    }

    #[Group('caja-blanca')]
    public function test_password_from_history_is_rejected(): void
    {
        $history = [$this->hasher->hash('Clave-Anterior-2025!')];

        $result = (new PasswordPolicy($this->hasher))->validate('Clave-Anterior-2025!', $history);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('últimas 5', (string) $result->firstError());
    }

    #[Group('caja-blanca')]
    public function test_compromised_password_is_rejected(): void
    {
        $checker = $this->checker(true);

        $result = (new PasswordPolicy($this->hasher, $checker))->validate('Clave-Segura-2026!');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('comprometida', (string) $result->firstError());
        $this->assertSame(1, $checker->calls);
    }

    #[Group('caja-blanca')]
    public function test_breach_service_is_not_called_when_the_password_already_fails(): void
    {
        $checker = $this->checker(true);

        (new PasswordPolicy($this->hasher, $checker))->validate('corta');

        $this->assertSame(0, $checker->calls, 'no se envía a un servicio externo una clave ya rechazada');
    }

    public function test_hash_is_argon2id_and_verifies(): void
    {
        $password = (new PasswordPolicy($this->hasher))->hash('Clave-Segura-2026!');

        $this->assertStringStartsWith('$argon2id$', $password->hash());
        $this->assertTrue($this->hasher->verify('Clave-Segura-2026!', $password->hash()));
        $this->assertFalse($this->hasher->verify('otra', $password->hash()));
    }

    private function checker(bool $compromised): CompromisedPasswordChecker
    {
        return new class($compromised) implements CompromisedPasswordChecker {
            public int $calls = 0;

            public function __construct(private bool $compromised) {}

            public function isCompromised(string $rawPassword): bool
            {
                $this->calls++;

                return $this->compromised;
            }
        };
    }
}
