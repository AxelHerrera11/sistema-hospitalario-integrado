<?php

declare(strict_types=1);

namespace Tests\Feature\Prescriptions;

use MicroHis\Prescripciones\Application\AllergyChecker;
use MicroHis\Prescripciones\Application\AuditLogger;
use MicroHis\Prescripciones\Application\MedicationCatalog;
use MicroHis\Prescripciones\Application\PrescriptionRecord;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Application\PrescriptionService;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Prescriptions\Concerns\PrescriptionApiSupport;
use Tests\TestCase;

#[Group('prescriptions-feature')]
final class PrescriptionSigningFeatureTest extends TestCase
{
    use PrescriptionApiSupport;

    public function test_treating_physician_can_sign_and_receives_200(): void
    {
        $this->bindSigningService($this->record());

        $this->postJson('/api/v1/prescriptions/prescription-1/sign')
            ->assertOk()
            ->assertJsonPath('status', 'signed');
    }

    public function test_signing_a_non_draft_prescription_returns_409(): void
    {
        $this->bindSigningService($this->record(true));

        $this->postJson('/api/v1/prescriptions/prescription-1/sign')
            ->assertStatus(409)
            ->assertJsonPath('code', 'PRESCRIPTION_NOT_DRAFT');
    }

    private function bindSigningService(PrescriptionRecord $record): void
    {
        $repository = Mockery::mock(PrescriptionRepository::class);
        $repository->shouldReceive('transaction')->andReturnUsing(static fn (callable $work) => $work());
        $repository->shouldReceive('findById')->once()->with('prescription-1')->andReturn($record);
        if ($record->prescription->isDraft()) {
            $repository->shouldReceive('save')->once()->with(Mockery::type(PrescriptionRecord::class));
        }

        $allergies = Mockery::mock(AllergyChecker::class);
        $allergies->shouldReceive('activeAllergies')->once()->with(self::PATIENT)->andReturn([]);
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('record')->zeroOrMoreTimes();

        $this->app->instance(PrescriptionService::class, new PrescriptionService(
            $repository,
            $allergies,
            Mockery::mock(MedicationCatalog::class),
            $audit,
        ));
    }
}
