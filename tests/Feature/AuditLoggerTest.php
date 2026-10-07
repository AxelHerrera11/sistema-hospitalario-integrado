<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_the_actor_entity_and_changes(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $entity = User::factory()->create(['tenant_id' => $tenant->id]);

        $log = app(AuditLogger::class)->log(
            action: 'updated',
            entity: $entity,
            oldValues: ['name' => 'Nombre anterior'],
            newValues: ['name' => 'Nombre nuevo'],
            user: $actor,
        );

        $stored = $log->fresh();

        $this->assertSame($tenant->id, $stored->tenant_id);
        $this->assertEquals($actor->id, $stored->user_id);
        $this->assertSame('updated', $stored->action);
        $this->assertSame('User', $stored->entity);
        $this->assertEquals($entity->id, $stored->entity_id);
        $this->assertSame(['name' => 'Nombre anterior'], $stored->old_values);
        $this->assertSame(['name' => 'Nombre nuevo'], $stored->new_values);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_it_rejects_an_actor_from_another_hospital(): void
    {
        $entity = User::factory()->create();
        $actor = User::factory()->create();

        try {
            app(AuditLogger::class)->log('updated', $entity, user: $actor);
            $this->fail('Debió rechazar al usuario de otro hospital.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'El usuario no pertenece al hospital de la entidad.',
                $exception->getMessage(),
            );
            $this->assertDatabaseCount('audit_logs', 0);
        }
    }

    public function test_it_rejects_an_entity_outside_the_current_hospital(): void
    {
        $currentTenant = Tenant::factory()->create();
        $entity = User::factory()->create();
        $this->app->instance('currentTenant', $currentTenant);

        try {
            app(AuditLogger::class)->log('viewed', $entity);
            $this->fail('Debió rechazar el registro de otro hospital.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'La entidad no pertenece al hospital actual.',
                $exception->getMessage(),
            );
            $this->assertSame(0, AuditLog::withoutGlobalScopes()->count());
        }
    }

    public function test_it_rejects_an_entity_that_has_not_been_saved(): void
    {
        $entity = User::factory()->make();

        $this->expectException(InvalidArgumentException::class);

        app(AuditLogger::class)->log('created', $entity);
    }

    public function test_it_uses_the_authenticated_user(): void
    {
        $actor = User::factory()->create();
        $entity = User::factory()->create(['tenant_id' => $actor->tenant_id]);
        $this->actingAs($actor, 'api');

        $log = app(AuditLogger::class)->log('viewed', $entity);

        $this->assertEquals($actor->id, $log->user_id);
        $this->assertSame($actor->tenant_id, $log->tenant_id);
    }
}
