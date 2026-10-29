<?php

declare(strict_types=1);

namespace Tests\Feature\Prescriptions;

use App\Http\Middleware\TenantMiddleware;
use App\Rbac\RequirePermission;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Prescriptions\Concerns\PrescriptionApiSupport;
use Tests\TestCase;

#[Group('prescriptions-feature')]
final class PrescriptionAccessFeatureTest extends TestCase
{
    use PrescriptionApiSupport;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->withMiddleware()->withoutMiddleware([TenantMiddleware::class, RequirePermission::class]);
        Auth::forgetGuards();

        $this->postJson($this->collectionUrl(), $this->draft())->assertUnauthorized();
    }

    public function test_user_without_doctor_profile_returns_403(): void
    {
        $this->context()->shouldReceive('doctorProfileForUser')->andReturn(null);

        $this->postJson($this->collectionUrl(), $this->draft())->assertForbidden();
    }

    public function test_missing_patient_returns_404(): void
    {
        $this->context()->shouldReceive('patientExists')->with(self::PATIENT)->andReturn(false);

        $this->getJson($this->collectionUrl())->assertNotFound()->assertJsonPath('code', 'PATIENT_NOT_FOUND');
    }
}
