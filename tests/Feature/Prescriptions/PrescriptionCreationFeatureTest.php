<?php

declare(strict_types=1);

namespace Tests\Feature\Prescriptions;

use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\Severity;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Prescriptions\Concerns\PrescriptionApiSupport;
use Tests\TestCase;

#[Group('prescriptions-feature')]
final class PrescriptionCreationFeatureTest extends TestCase
{
    use PrescriptionApiSupport;

    public function test_clear_prescription_returns_201(): void
    {
        $this->bindService();

        $this->postJson($this->collectionUrl(), $this->draft())
            ->assertCreated()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('warnings', []);
    }

    public function test_mild_allergy_returns_201_with_warning(): void
    {
        $this->bindService([new Allergy(null, 'Amoxicilina', AllergyType::Allergy, Severity::Mild)]);

        $this->postJson($this->collectionUrl(), $this->draft())
            ->assertCreated()
            ->assertJsonPath('warnings', ['ALLERGY_CONFLICT']);
    }
}
