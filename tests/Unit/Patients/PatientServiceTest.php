<?php

declare(strict_types=1);

namespace Tests\Unit\Patients;

use MicroHis\Pacientes\Application\PatientOperationFailure;
use MicroHis\Pacientes\Application\PatientService;
use MicroHis\Pacientes\Persistence\InMemoryPatientRepository;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/micro-his/pacientes/bootstrap.php';

final class PatientServiceTest extends TestCase
{
    private InMemoryPatientRepository $repository;
    private PatientService $service;

    protected function setUp(): void
    {
        $this->repository = new InMemoryPatientRepository();
        $this->service = new PatientService($this->repository);
    }

    public function test_future_birth_date_is_rejected_by_domain_through_service(): void
    {
        try {
            $this->service->register($this->draft(['birth_date' => '2999-01-01']));
            self::fail('Expected invalid future date.');
        } catch (PatientOperationFailure $failure) {
            self::assertSame('BIRTH_DATE_FUTURE', $failure->errorCode);
            self::assertSame('birth_date', $failure->field);
        }
    }

    public function test_duplicate_code_takes_the_service_conflict_branch(): void
    {
        $this->service->register($this->draft());

        try {
            $this->service->register($this->draft());
            self::fail('Expected duplicate code rejection.');
        } catch (PatientOperationFailure $failure) {
            self::assertSame('PATIENT_CODE_EXISTS', $failure->errorCode);
            self::assertSame('code', $failure->field);
        }
    }

    public function test_update_changes_demographics_without_changing_identity(): void
    {
        $created = $this->service->register($this->draft());
        $updated = $this->service->update('TEST-001', ['given_name' => 'Actualizado']);

        self::assertSame('Actualizado', $updated->givenName);
        self::assertSame($created->id, $updated->id);
        self::assertSame($created->globalId, $updated->globalId);
        self::assertSame($created->code, $updated->code);
    }

    public function test_search_returns_the_existing_patient_branch(): void
    {
        $created = $this->service->register($this->draft());

        self::assertSame($created->id, $this->service->search('TEST-001')->id);
    }

    public function test_search_missing_patient_throws_not_found_branch(): void
    {
        try {
            $this->service->search('MISSING');
            self::fail('Expected patient not found.');
        } catch (PatientOperationFailure $failure) {
            self::assertSame('PATIENT_NOT_FOUND', $failure->errorCode);
        }
    }

    private function draft(array $overrides = []): array
    {
        return array_replace([
            'global_id' => '11111111-1111-4111-8111-111111111111',
            'code' => 'TEST-001',
            'given_name' => 'Paciente',
            'family_name' => 'Prueba',
            'birth_date' => '2000-01-01',
            'sex' => 'unknown',
        ], $overrides);
    }
}
