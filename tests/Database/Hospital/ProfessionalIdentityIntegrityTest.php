<?php

namespace Tests\Database\Hospital;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class ProfessionalIdentityIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_local_professional_inventory_uses_uuid_timestamptz_and_logical_central_references(): void
    {
        foreach (['local_user_refs', 'specialties', 'doctor_profiles', 'doctor_specialties'] as $table) {
            $this->assertTrue($this->tableExists($table));
        }

        $this->assertSame('uuid', $this->columnType('local_user_refs', 'local_user_ref_id'));
        $this->assertSame('uuid', $this->columnType('local_user_refs', 'central_user_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('local_user_refs', 'synced_at'));
        $this->assertSame('date', $this->columnType('doctor_profiles', 'valid_from'));
        $this->assertSame('timestamp with time zone', $this->columnType('doctor_profiles', 'created_at'));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM information_schema.columns WHERE table_name = 'local_user_refs' AND column_name IN ('password', 'password_hash', 'token', 'access_token', 'refresh_token', 'verifier', 'offline_auth_claim')"));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM information_schema.table_constraints WHERE table_name = 'local_user_refs' AND constraint_type = 'FOREIGN KEY'"));
        $this->assertSame('Logical CENTRAL central_users UUID reference; no physical cross-database foreign key.', $this->columnComment('local_user_refs', 'central_user_id'));
        $this->assertSame('Logical CENTRAL hospital_memberships UUID reference; no physical cross-database foreign key.', $this->columnComment('local_user_refs', 'membership_id'));
    }

    public function test_every_local_h1_foreign_key_column_has_an_index(): void
    {
        foreach ([
            ['doctor_profiles', 'local_user_ref_id'],
            ['doctor_specialties', 'doctor_profile_id'],
            ['doctor_specialties', 'specialty_id'],
        ] as [$table, $column]) {
            $this->assertSame(1, (int) DB::connection('hospital')->scalar(
                'SELECT count(*) FROM pg_constraint fk JOIN pg_class relation ON relation.oid = fk.conrelid JOIN pg_attribute attribute ON attribute.attrelid = relation.oid AND attribute.attnum = ANY(fk.conkey) WHERE fk.contype = \'f\' AND relation.relname = ? AND attribute.attname = ? AND EXISTS (SELECT 1 FROM pg_index indexed WHERE indexed.indrelid = relation.oid AND attribute.attnum = ANY(indexed.indkey))',
                [$table, $column],
            ));
        }
    }

    public function test_local_identity_and_professional_constraints_reject_invalid_writes(): void
    {
        $db = DB::connection('hospital');
        $this->insertValidProfessionalGraph();

        $this->assertRejected(fn () => $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000101', '00000000-0000-0000-0000-000000000202', 'Duplicate', 'physician', 'active', now())"));
        $this->assertRejectedWithConstraint(fn () => $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, status, valid_from) VALUES ('00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000001', 'MED-001', 'active', now())"), 'doctor_profiles_license_number_unique');
        $this->assertRejected(fn () => $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, is_primary, valid_from, valid_to, status) VALUES ('00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', false, now(), now() - interval '1 day', 'active')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, is_primary, valid_from, status) VALUES ('00000000-0000-0000-0000-000000000008', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', false, now(), 'active')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, is_primary, valid_from, status) VALUES ('00000000-0000-0000-0000-000000000009', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', true, now() + interval '1 day', 'active')"));
    }

    public function test_duplicate_membership_id_is_rejected(): void
    {
        $this->insertValidProfessionalGraph();

        $this->assertRejectedWithConstraint(fn () => DB::connection('hospital')->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000110', '00000000-0000-0000-0000-000000000201', 'Duplicate membership', 'physician', 'active', now())"), 'local_user_refs_membership_unique');
    }

    public function test_invalid_doctor_and_specialty_statuses_are_rejected(): void
    {
        $this->insertValidProfessionalGraph();

        $this->assertRejected(fn () => DB::connection('hospital')->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, status, valid_from) VALUES ('00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 'MED-011', 'invalid', now())"));
        $this->assertRejected(fn () => DB::connection('hospital')->insert("INSERT INTO specialties (specialty_id, code, name, status) VALUES ('00000000-0000-0000-0000-000000000012', 'INVALID', 'Invalid specialty', 'invalid')"));
    }

    public function test_invalid_doctor_profile_date_range_is_rejected(): void
    {
        $this->insertValidProfessionalGraph();

        $this->assertRejected(fn () => DB::connection('hospital')->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, status, valid_from, valid_to) VALUES ('00000000-0000-0000-0000-000000000013', '00000000-0000-0000-0000-000000000001', 'MED-013', 'active', now(), now() - interval '1 day')"));
    }

    private function columnType(string $table, string $column): string
    {
        return DB::connection('hospital')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $column]);
    }

    private function columnComment(string $table, string $column): ?string
    {
        return DB::connection('hospital')->scalar('SELECT col_description(?::regclass, ordinal_position) FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $table, $column]);
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('hospital')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function insertValidProfessionalGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000101', '00000000-0000-0000-0000-000000000201', 'Dr. Ada', 'physician', 'active', now())");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, status, valid_from) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'MED-001', 'active', now())");
        $db->insert("INSERT INTO specialties (specialty_id, code, name, status) VALUES ('00000000-0000-0000-0000-000000000003', 'CARD', 'Cardiology', 'active')");
        $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, is_primary, valid_from, status) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', true, now(), 'active')");
    }

    private function assertRejected(Closure $statement): void
    {
        try {
            $statement();
            $this->fail('Expected PostgreSQL to reject the invalid write.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    private function assertRejectedWithConstraint(Closure $statement, string $constraint): void
    {
        try {
            $statement();
            $this->fail('Expected PostgreSQL to reject the invalid write.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString($constraint, $exception->getMessage());
        }
    }
}
