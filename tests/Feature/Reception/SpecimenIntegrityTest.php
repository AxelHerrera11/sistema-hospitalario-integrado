<?php

namespace Tests\Feature\Reception;

use App\Modules\Reception\Infrastructure\HospitalReceptionRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use App\Modules\Reception\Domain\SpecimenCode;
use Tests\TestCase;

#[Group('database-integrity')]
class SpecimenIntegrityTest extends TestCase
{
    private string $patient;

    private string $episode;

    private string $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->patient = (string) Str::uuid();
        $this->episode = (string) Str::uuid();
        $this->actor = (string) Str::uuid();
        DB::connection('hospital')->table('local_user_refs')->insert(['local_user_ref_id' => $this->actor, 'central_user_id' => Str::uuid(), 'membership_id' => Str::uuid(), 'display_name' => 'Técnica Demo', 'role_code' => 'tecnico_laboratorio', 'synced_at' => now()]);
        DB::connection('hospital')->table('local_patients')->insert(['local_patient_id' => $this->patient, 'global_id' => Str::uuid(), 'code' => 'REC-001', 'given_name' => 'Ana', 'family_name' => 'Demo', 'birth_date' => '2000-01-01', 'sex' => 'female']);
        DB::connection('hospital')->table('care_episodes')->insert(['care_episode_id' => $this->episode, 'local_patient_id' => $this->patient, 'type' => 'outpatient', 'started_at' => now(), 'reason' => 'Fixture ficticio']);
    }

    public function test_types_uuid_numeric_timestamp_and_no_tenant_column(): void
    {
        $types = array_column(DB::connection('hospital')->select("SELECT column_name,data_type FROM information_schema.columns WHERE table_schema='public' AND table_name='specimens'"), 'data_type', 'column_name');
        foreach (['specimen_id', 'lab_order_item_id', 'local_patient_id', 'care_episode_id', 'received_by_user_ref_id', 'request_key'] as $column) {
            $this->assertSame('uuid', $types[$column]);
        }
        $this->assertSame('numeric', $types['volume']);
        $this->assertSame('numeric', $types['temperature']);
        $this->assertSame('timestamp with time zone', $types['received_at']);
        $this->assertArrayNotHasKey('tenant_id', $types);
    }

    public function test_local_foreign_keys_are_restrict_and_indexed_and_order_is_logical(): void
    {
        $foreign = DB::connection('hospital')->select("SELECT pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid='specimens'::regclass AND contype='f'");
        $this->assertCount(3, $foreign);
        $definitions = implode(' ', array_column($foreign, 'definition'));
        $this->assertStringNotContainsString('lab_order_item_id', $definitions);
        $indexes = implode(' ', array_column(DB::connection('hospital')->select("SELECT indexdef FROM pg_indexes WHERE tablename='specimens'"), 'indexdef'));
        foreach (['local_patient_id', 'care_episode_id', 'received_by_user_ref_id'] as $column) {
            $this->assertStringContainsString('FOREIGN KEY ('.$column.')', $definitions);
            $this->assertStringContainsString('('.$column.')', $indexes);
        }
        foreach ($foreign as $constraint) {
            $this->assertStringContainsString('ON DELETE RESTRICT', $constraint->definition);
        }
        $this->assertStringContainsString('(lab_order_item_id)', $indexes);
        $this->insert(); // El UUID de orden no requiere una tabla externa ni un registro local.
    }

    public function test_checks_uniqueness_and_real_local_references(): void
    {
        $saved = $this->insert();
        foreach ([['volume' => -1], ['temperature' => 101], ['status' => 'unknown'], ['status' => 'rejected', 'reason' => ' ']] as $changes) {
            $this->rejected($changes, '23514');
        }
        $this->rejected(['code' => $saved['code']], '23505');
        $this->rejected(['request_key' => $saved['request_key']], '23505');
        foreach (['local_patient_id', 'care_episode_id', 'received_by_user_ref_id'] as $column) {
            $this->rejected([$column => (string) Str::uuid()], '23503');
        }
    }

    public function test_specimen_and_idempotent_outbox_event_are_atomic(): void
    {
        $sample = $this->row();
        foreach (['specimen_id' => 'id', 'lab_order_item_id' => 'item_id', 'received_by_user_ref_id' => 'actor_id'] as $from => $to) {
            $sample[$to] = $sample[$from];
            unset($sample[$from]);
        }
        $repository = new HospitalReceptionRepository;
        try {
            $repository->transaction(function () use ($repository, $sample): void {
                $repository->save($sample);
                throw new \RuntimeException('fallo simulado');
            });
        } catch (\RuntimeException $error) {
            $this->assertSame('fallo simulado', $error->getMessage());
        }
        $this->assertSame(0, DB::connection('hospital')->table('specimens')->count());
        $this->assertSame(0, DB::connection('hospital')->table('outbox_events')->count());
        $repository->transaction(fn () => $repository->save($sample));
        $this->assertSame($sample['id'], DB::connection('hospital')->table('outbox_events')->value('event_id'));
        $this->assertSame(1, DB::connection('hospital')->table('specimens')->count());
    }

    public function test_rollback_slot_000601_preserves_medications_and_reinstalls(): void
    {
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => $this->hospitalRollbackSteps('2026_08_11_000601'), '--force' => true]);
        $this->assertFalse(DB::connection('hospital')->getSchemaBuilder()->hasTable('specimens'));
        $this->assertTrue(DB::connection('hospital')->getSchemaBuilder()->hasTable('medications'));
        Artisan::call('migrate', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->assertTrue(DB::connection('hospital')->getSchemaBuilder()->hasTable('specimens'));
    }

    private function row(array $changes = []): array
    {
        return array_replace(['specimen_id' => (string) Str::uuid(), 'lab_order_item_id' => (string) Str::uuid(), 'local_patient_id' => $this->patient, 'care_episode_id' => $this->episode, 'received_by_user_ref_id' => $this->actor, 'request_key' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'volume' => 3, 'temperature' => 20, 'status' => 'accepted', 'reason' => null, 'code' => SpecimenCode::generate(), 'received_at' => now()->toIso8601String()], $changes);
    }

    private function insert(array $changes = []): array
    {
        $row = $this->row($changes);
        DB::connection('hospital')->table('specimens')->insert($row);

        return $row;
    }

    private function rejected(array $changes, string $state): void
    {
        try {
            DB::connection('hospital')->transaction(fn () => $this->insert($changes));
            $this->fail('Se aceptó una violación de integridad');
        } catch (QueryException $error) {
            $this->assertSame($state, (string) $error->getCode());
        }
    }
}
