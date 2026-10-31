<?php

declare(strict_types=1);

namespace Tests\Unit\Reception;

use App\Modules\Reception\Application\ReceiveSpecimen;
use App\Modules\Reception\Application\OrderGateway;
use App\Modules\Reception\Application\ReceptionRepository;
use App\Modules\Reception\Domain\CodeCollision;
use App\Modules\Reception\Domain\SpecimenCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;


final class ReceiveSpecimenTest extends TestCase
{
    public static function invalidSamples(): array
    {
        return [
            ['patient_code', 'P002', 'identificacion_discordante'],
            ['patient_name', 'Otra persona', 'identificacion_discordante'],
            ['type', 'urine', 'tipo_incorrecto'], ['container', 'otro', 'contenedor_incorrecto'],
            ['volume', 2.9, 'volumen_insuficiente'], ['temperature', 14.9, 'temperatura_inadecuada'],
            ['temperature', 25.1, 'temperatura_inadecuada'],
            ['collected_at', '2026-10-20T09:59:59-06:00', 'fuera_de_estabilidad'],
            ['collected_at', '2026-10-20T11:00:01-06:00', 'fuera_de_estabilidad'],
            ['hemolizada', true, 'hemolizada'], ['coagulada', true, 'coagulada'], ['derramada', true, 'derramada'],
            ['sin_etiqueta', true, 'sin_etiqueta'],
        ];
    }

    #[DataProvider('invalidSamples')]
    public function test_rejects_invalid_observations(string $field, mixed $value, string $reason): void
    {
        [$repo, $input] = self::fixture();
        $input[$field] = $value;
        $result = (new ReceiveSpecimen($repo, $repo))->execute('H1', 'T1', $input, self::now());
        self::assertSame('rejected', $result['status']);
        self::assertSame($reason, $result['reason']);
        self::assertNull($result['code']);
    }

    public static function sampleTypes(): array
    {
        return [['blood'], ['urine'], ['swab']];
    }

    #[DataProvider('sampleTypes')]
    public function test_accepts_boundaries_and_replays_same_request(string $type): void
    {
        [$repo, $input] = self::fixture();
        $repo->type = $input['type'] = $type;
        $service = new ReceiveSpecimen($repo, $repo);
        $first = $service->execute('H1', 'T1', $input, self::now());
        self::assertTrue(SpecimenCode::valid($first['code']));
        self::assertSame($first, $service->execute('H1', 'T1', $input, self::now()));
        self::assertCount(1, $repo->samples);
    }

    public function test_changed_payload_cannot_reuse_request_key(): void
    {
        [$repo, $input] = self::fixture();
        $service = new ReceiveSpecimen($repo, $repo);
        $service->execute('H1', 'T1', $input, self::now());
        $input['volume'] = 4;
        $this->expectException(\DomainException::class);
        $this->expectExceptionCode(409);
        $service->execute('H1', 'T1', $input, self::now());
    }

    public function test_only_code_collisions_are_retried_at_most_three_times(): void
    {
        [$repo, $input] = self::fixture();
        $repo->collisions = 2;
        (new ReceiveSpecimen($repo, $repo))->execute('H1', 'T1', $input, self::now());
        self::assertSame(3, $repo->attempts);
        [$repo, $input] = self::fixture();
        $repo->collisions = 3;
        try {
            (new ReceiveSpecimen($repo, $repo))->execute('H1', 'T1', $input, self::now());
            self::fail();
        } catch (CodeCollision) {
            self::assertSame(3, $repo->attempts);
            self::assertSame([], $repo->samples);
        }
    }

    public function test_other_persistence_errors_are_not_retried(): void
    {
        [$repo, $input] = self::fixture();
        $repo->broken = true;
        try {
            (new ReceiveSpecimen($repo, $repo))->execute('H1', 'T1', $input, self::now());
            self::fail();
        } catch (\RuntimeException) {
            self::assertSame(1, $repo->attempts);
            self::assertSame([], $repo->samples);
        }
    }

    public static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-10-20T11:00:00-06:00');
    }

    public static function fixture(): array
    {
        return [new ReceptionMemory, ['request_key' => 'd45ada7a-b4a3-4dc0-aea2-b8ffac2d12ab',
            'item_id' => '11111111-1111-4111-8111-111111111111', 'patient_code' => 'P001', 'patient_name' => 'Ana Demo', 'type' => 'blood',
            'container' => 'EDTA', 'volume' => 3, 'temperature' => 15, 'collected_at' => '2026-10-20T10:00:00-06:00']];
    }
}

/** Test double only: production uses PostgreSQL. */
final class ReceptionMemory implements ReceptionRepository, OrderGateway
{
    public array $samples = [];

    public int $collisions = 0;

    public int $attempts = 0;

    public bool $broken = false;

    public string $type = 'blood';

    public function transaction(callable $operation): mixed
    {
        return $operation();
    }

    public function order(string $tenant, string $item): ?array
    {
        if ($tenant !== 'H1' || $item !== '11111111-1111-4111-8111-111111111111') {
            return null;
        }

        return ['status' => $this->samples ? 'received' : 'pending', 'patient_code' => 'P001',
            'patient_name' => 'Ana Demo', 'type' => $this->type, 'container' => 'EDTA', 'min_volume' => 3,
            'local_patient_id' => '22222222-2222-4222-8222-222222222222', 'care_episode_id' => '33333333-3333-4333-8333-333333333333', 'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25];
    }

    public function save(array $sample): void
    {
        $this->attempts++;
        if ($this->broken) {
            throw new \RuntimeException('Storage unavailable');
        }
        if ($this->collisions-- > 0) {
            throw new CodeCollision;
        }
        $this->samples[] = $sample;
    }

    public function find(string $tenant, string $code): ?array
    {
        return null;
    }

    public function byRequest(string $tenant, string $key): ?array
    {
        foreach ($this->samples as $sample) {
            if ($tenant === 'H1' && $sample['request_key'] === $key) {
                return $sample;
            }
        }

        return null;
    }
}