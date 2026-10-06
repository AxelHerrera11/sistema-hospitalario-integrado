<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Formato común de errores (docs/contrato-api.md §4): 404 genérico que no
 * expone clases internas y mensajes de validación en español.
 */
class ApiErrorFormatTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->receptionist = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->receptionist->assignRole('Recepcionista');
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($this->receptionist),
            'X-Tenant-ID' => $this->tenant->id,
        ];
    }

    public function test_unknown_and_foreign_records_return_the_same_generic_404(): void
    {
        $foreign = Patient::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);

        foreach (["/api/v1/patients/{$foreign->id}", '/api/v1/patients/999999'] as $uri) {
            $response = $this->withHeaders($this->headers())->getJson($uri)
                ->assertNotFound()
                ->assertExactJson(['message' => 'Recurso no encontrado.']);

            $this->assertStringNotContainsString('App\\\\Models', $response->getContent());
        }
    }

    public function test_unknown_api_route_returns_the_generic_404(): void
    {
        $this->withHeaders($this->headers())->getJson('/api/v1/ruta-inexistente')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Recurso no encontrado.']);
    }

    public function test_validation_errors_are_in_spanish_with_readable_field_names(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/v1/patients', ['first_name' => 'Ana'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El campo apellido es obligatorio. (y 2 errores más)')
            ->assertJsonPath('errors.last_name.0', 'El campo apellido es obligatorio.')
            ->assertJsonPath('errors.birth_date.0', 'El campo fecha de nacimiento es obligatorio.')
            ->assertJsonPath('errors.gender.0', 'El campo género es obligatorio.');
    }
}
