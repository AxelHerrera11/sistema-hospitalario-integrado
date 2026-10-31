<?php

namespace Tests\Feature\Reception;

use App\Modules\Reception\Infrastructure\OrderProjection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Tenants\TenantAuthTestCase;

abstract class ReceptionTestCase extends TenantAuthTestCase
{
    protected string $schema;
    protected string $patient;
    protected string $episode;
    protected string $actor;
    protected string $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema = 'reception_'.bin2hex(random_bytes(6));
        $database = config('database.connections.hospital.database');
        if (!str_ends_with($database, '_test')) throw new \RuntimeException('Solo bases *_test');
        DB::connection('hospital')->statement('CREATE SCHEMA '.$this->schema);
        config(['database.connections.hospital.search_path' => $this->schema.',public', 'reception.hospital_id' => $this->hospitalA]);
        DB::purge('hospital');
        foreach (glob(database_path('migrations/hospital/*.php')) as $file) (require $file)->up();
        $this->patient = (string) Str::uuid(); $this->episode = (string) Str::uuid();
        $this->actor = (string) Str::uuid(); $this->item = (string) Str::uuid();
        DB::connection('hospital')->table('local_user_refs')->insert(['local_user_ref_id' => $this->actor,
            'central_user_id' => $this->userId, 'membership_id' => Str::uuid(), 'display_name' => 'Técnica Demo',
            'role_code' => 'tecnico_laboratorio', 'synced_at' => now()]);
        DB::connection('hospital')->table('local_patients')->insert(['local_patient_id' => $this->patient,
            'global_id' => Str::uuid(), 'code' => 'P001', 'given_name' => 'Ana', 'family_name' => 'Demo',
            'birth_date' => '2000-01-01', 'sex' => 'female']);
        DB::connection('hospital')->table('care_episodes')->insert(['care_episode_id' => $this->episode,
            'local_patient_id' => $this->patient, 'type' => 'outpatient', 'started_at' => now(), 'reason' => 'Prueba ficticia']);
        $this->projection();
    }

    protected function tearDown(): void
    {
        if (isset($this->schema)) {
            $pdo = DB::connection('hospital')->getPdo();
            if ($pdo->inTransaction()) $pdo->rollBack();
            DB::connection('hospital')->statement('DROP SCHEMA '.$this->schema.' CASCADE');
            DB::purge('hospital');
        }
        parent::tearDown();
    }

    protected function projection(): array
    {
        $row = ['tenant_id' => $this->hospitalA, 'item_id' => $this->item,
            'local_patient_id' => $this->patient, 'care_episode_id' => $this->episode,
            'patient_code' => 'P001', 'patient_name' => 'Ana Demo', 'type' => 'blood', 'container' => 'EDTA',
            'min_volume' => 3, 'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25, 'status' => 'pending'];
        (new OrderProjection)->import($row);
        return $row;
    }

    protected function payload(): array
    {
        return ['request_key' => (string) Str::uuid(), 'item_id' => $this->item, 'patient_code' => 'P001',
            'patient_name' => 'Ana Demo', 'type' => 'blood', 'container' => 'EDTA', 'volume' => 3,
            'temperature' => 20, 'collected_at' => now()->subMinute()->format(DATE_ATOM)];
    }

    protected function signIn(string $role = 'tecnico_laboratorio'): void
    {
        $this->addMembership($this->userId, $this->hospitalA, $role);
        $token = $this->tokenFrom($this->login()->assertOk());
        $this->withToken($token)->withHeader('X-Tenant-ID', $this->hospitalA);
    }
}
