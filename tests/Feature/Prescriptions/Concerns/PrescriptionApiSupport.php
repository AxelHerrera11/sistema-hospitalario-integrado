<?php

declare(strict_types=1);

namespace Tests\Feature\Prescriptions\Concerns;

use App\Modules\Tenants\Infrastructure\Persistence\CentralUser;
use App\Prescripciones\PdoPrescriptionContext;
use DateTimeImmutable;
use MicroHis\Prescripciones\Application\AllergyChecker;
use MicroHis\Prescripciones\Application\AuditLogger;
use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\MedicationCatalog;
use MicroHis\Prescripciones\Application\PrescriptionRecord;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Application\PrescriptionService;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\Prescription;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use Mockery;

trait PrescriptionApiSupport
{
    private const PATIENT = '00000000-0000-4000-8000-000000000001';

    private const DOCTOR = '00000000-0000-4000-8000-000000000002';

    private const EPISODE = '00000000-0000-4000-8000-000000000003';

    private const MEDICATION = '00000000-0000-4000-8000-000000000004';

    private const USER = '00000000-0000-4000-8000-000000000005';

    protected function setUp(): void
    {
        parent::setUp();
        $context = Mockery::mock(PdoPrescriptionContext::class);
        $context->shouldReceive('patientExists')->byDefault()->andReturn(true);
        $context->shouldReceive('openCareEpisodeForPatient')->byDefault()->andReturn(self::EPISODE);
        $context->shouldReceive('doctorProfileForUser')->byDefault()->andReturn(self::DOCTOR);
        $this->app->instance(PdoPrescriptionContext::class, $context);
        config(['jwt.secret' => 'prescriptions-feature-test-key']);
        $this->withoutMiddleware();
        $this->actingAs(new CentralUser(['user_id' => self::USER]), 'api');
    }

    private function bindService(array $allergies = [], ?PrescriptionRepository $repository = null, ?MedicationNotFound $medicationFailure = null, ?AllergyCheckUnavailable $allergyFailure = null): void
    {
        if ($repository === null) {
            $repository = Mockery::mock(PrescriptionRepository::class);
            $repository->shouldReceive('save')->once();
        }
        $repository->shouldReceive('transaction')->andReturnUsing(static fn (callable $work) => $work());
        $checker = Mockery::mock(AllergyChecker::class);
        $checker->shouldReceive('activeAllergies')->with(self::PATIENT)
            ->andReturnUsing(static function () use ($allergies, $allergyFailure): array {
                if ($allergyFailure !== null) {
                    throw $allergyFailure;
                }

                return $allergies;
            });
        $catalog = Mockery::mock(MedicationCatalog::class);
        $catalog->shouldReceive('find')->once()->with(self::MEDICATION)
            ->andReturnUsing(static function () use ($medicationFailure): MedicationSnapshot {
                if ($medicationFailure !== null) {
                    throw $medicationFailure;
                }

                return new MedicationSnapshot(self::MEDICATION, 'AMX500', 'Amoxicilina', 'Amoxicilina', 'Penicilinas', true);
            });
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('record')->zeroOrMoreTimes();
        $this->app->instance(PrescriptionService::class, new PrescriptionService($repository, $checker, $catalog, $audit));
    }

    private function record(bool $signed = false): PrescriptionRecord
    {
        $medication = new MedicationSnapshot(self::MEDICATION, 'AMX500', 'Amoxicilina', 'Amoxicilina', 'Penicilinas', true);
        $prescription = Prescription::createDraft('prescription-1', self::PATIENT, self::DOCTOR, self::EPISODE, $medication, PrescriptionRoute::Oral, '500 mg', '2', '7');
        if ($signed) {
            $prescription = $prescription->sign(self::DOCTOR, new DateTimeImmutable, []);
        }

        return new PrescriptionRecord($prescription, 'clear', '500', 'mg', 2, 'day', 7, 'days');
    }

    private function draft(): array
    {
        return [
            'medication_id' => self::MEDICATION,
            'dosage_value' => 500,
            'dosage_unit' => 'mg',
            'route' => 'oral',
            'frequency_times' => 2,
            'frequency_interval' => 'day',
            'duration_value' => 7,
            'duration_unit' => 'days',
        ];
    }

    private function collectionUrl(): string
    {
        return '/api/v1/patients/'.self::PATIENT.'/prescriptions';
    }

    private function context(): PdoPrescriptionContext
    {
        return $this->app->make(PdoPrescriptionContext::class);
    }
}
