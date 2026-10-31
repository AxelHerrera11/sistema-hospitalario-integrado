<?php

declare(strict_types=1);

namespace Tests\Feature\Prescriptions;

use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\Severity;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Prescriptions\Concerns\PrescriptionApiSupport;
use Tests\TestCase;

#[Group('prescriptions-feature')]
final class PrescriptionRejectionFeatureTest extends TestCase
{
    use PrescriptionApiSupport;

    public function test_invalid_body_returns_422(): void
    {
        $this->postJson($this->collectionUrl(), [])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_u03_moderate_allergy_returns_422_without_saving(): void
    {
        $repository = Mockery::mock(PrescriptionRepository::class);
        $repository->shouldNotReceive('save');
        $this->bindService([new Allergy(null, 'Amoxicilina', AllergyType::Allergy, Severity::Moderate)], $repository);

        $this->postJson($this->collectionUrl(), $this->draft())
            ->assertUnprocessable()
            ->assertJsonPath('code', 'ALLERGY_CONFLICT')
            ->assertJsonPath('severity', 'moderate')
            ->assertJsonPath('exception_allowed', false);
    }

    public function test_u04_unknown_allergy_returns_422_without_saving(): void
    {
        $repository = Mockery::mock(PrescriptionRepository::class);
        $repository->shouldNotReceive('save');
        $this->bindService([new Allergy(null, 'Amoxicilina', AllergyType::Allergy, Severity::Unknown)], $repository);

        $this->postJson($this->collectionUrl(), $this->draft())
            ->assertUnprocessable()
            ->assertJsonPath('code', 'ALLERGY_CONFLICT')
            ->assertJsonPath('severity', 'unknown')
            ->assertJsonPath('exception_allowed', false);
    }

    public function test_inactive_medication_returns_422_without_saving(): void
    {
        $repository = Mockery::mock(PrescriptionRepository::class);
        $repository->shouldNotReceive('save');
        $this->bindService([], $repository, new MedicationNotFound(self::MEDICATION));

        $this->postJson($this->collectionUrl(), $this->draft())
            ->assertUnprocessable()
            ->assertJsonPath('code', 'MEDICATION_INACTIVE');
    }

    public function test_unavailable_dependency_returns_503_without_saving(): void
    {
        $repository = Mockery::mock(PrescriptionRepository::class);
        $repository->shouldNotReceive('save');
        $this->bindService([], $repository, null, new AllergyCheckUnavailable);

        $this->postJson($this->collectionUrl(), $this->draft())
            ->assertStatus(503)
            ->assertJsonPath('code', 'PRESCRIPTION_DEPENDENCY_UNAVAILABLE');
    }
}
