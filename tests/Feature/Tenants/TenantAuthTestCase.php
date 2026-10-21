<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class TenantAuthTestCase extends TestCase
{
    protected string $hospitalA;

    protected string $hospitalB;

    protected string $userId;

    protected string $networkId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        $this->app['config']->set('jwt.secret', str_repeat('testing-secret-', 4));
        $this->app['config']->set('jwt.ttl', 15);
        $this->app['config']->set('jwt.blacklist_enabled', false);

        DB::purge('central');

        $this->createSchema();

        $this->hospitalA = (string) Str::uuid();
        $this->hospitalB = (string) Str::uuid();
        $this->userId = (string) Str::uuid();
        $this->networkId = (string) Str::uuid();

        $this->createNetwork($this->networkId);
        $this->createHospital($this->hospitalA, 'Hospital Central');
        $this->createHospital($this->hospitalB, 'Hospital Norte');
        $this->createUser($this->userId, 'doctor@example.test');
        $this->createCredential($this->userId, 'doctor@example.test', 'secret-password');
    }

    protected function createSchema(): void
    {
        $statements = [
            "CREATE TABLE networks (
                network_id text PRIMARY KEY,
                name text NOT NULL,
                status text NOT NULL,
                timezone text NOT NULL,
                metadata text NOT NULL DEFAULT '{}',
                created_at datetime,
                updated_at datetime
            )",
            "CREATE TABLE central_users (
                user_id text PRIMARY KEY,
                subject text NOT NULL,
                email text,
                display_name text NOT NULL,
                status text NOT NULL,
                last_authenticated_at datetime,
                metadata text NOT NULL DEFAULT '{}',
                created_at datetime,
                updated_at datetime
            )",
            "CREATE TABLE hospitals (
                hospital_id text PRIMARY KEY,
                network_id text,
                code text NOT NULL,
                name text NOT NULL,
                status text NOT NULL,
                timezone text,
                clinical_endpoint text,
                schema_version text,
                metadata text NOT NULL DEFAULT '{}',
                created_at datetime,
                updated_at datetime
            )",
            "CREATE TABLE hospital_memberships (
                membership_id text PRIMARY KEY,
                user_id text NOT NULL,
                hospital_id text NOT NULL,
                role_code text NOT NULL,
                status text NOT NULL,
                valid_from datetime NOT NULL,
                valid_to datetime,
                metadata text NOT NULL DEFAULT '{}',
                created_at datetime,
                updated_at datetime
            )",
            'CREATE TABLE auth_credentials (
                user_id text PRIMARY KEY,
                email_normalized text NOT NULL UNIQUE,
                password_hash text NOT NULL,
                created_at datetime,
                updated_at datetime
            )',
            'CREATE TABLE auth_sessions (
                session_id text PRIMARY KEY,
                user_id text NOT NULL,
                hospital_id text NOT NULL,
                jti text NOT NULL UNIQUE,
                issued_at datetime NOT NULL,
                expires_at datetime NOT NULL,
                revoked_at datetime,
                revoked_reason text,
                created_at datetime,
                updated_at datetime
            )',
            'CREATE TABLE auth_refresh_tokens (
                refresh_token_id text PRIMARY KEY,
                family_id text NOT NULL,
                session_id text NOT NULL,
                user_id text NOT NULL,
                hospital_id text NOT NULL,
                token_hash text NOT NULL UNIQUE,
                issued_at datetime NOT NULL,
                expires_at datetime NOT NULL,
                consumed_at datetime,
                revoked_at datetime,
                revoked_reason text,
                created_at datetime,
                updated_at datetime
            )',
            'CREATE TABLE audit_log (
                event_id text PRIMARY KEY,
                occurred_at text NOT NULL,
                event text NOT NULL,
                actor_id text NOT NULL,
                hospital_id text NOT NULL,
                detail text NOT NULL,
                trace_id text,
                result text,
                previous_hash text NOT NULL,
                event_hash text NOT NULL
            )',
            'CREATE TABLE audit_chain_state (
                chain_id integer PRIMARY KEY,
                head_hash text NOT NULL
            )',
        ];

        foreach ($statements as $statement) {
            DB::connection('central')->statement($statement);
        }

        DB::connection('central')->table('audit_chain_state')->insert([
            'chain_id' => 1,
            'head_hash' => str_repeat('0', 64),
        ]);
    }

    protected function createHospital(string $id, string $name, string $status = 'active'): void
    {
        DB::connection('central')->table('hospitals')->insert([
            'hospital_id' => $id,
            'network_id' => $this->networkId,
            'code' => 'H-'.$id,
            'name' => $name,
            'status' => $status,
            'timezone' => 'UTC',
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createNetwork(string $id, string $name = 'Red hospitalaria'): void
    {
        DB::connection('central')->table('networks')->insert([
            'network_id' => $id,
            'name' => $name,
            'status' => 'active',
            'timezone' => 'UTC',
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createUser(string $id, string $email, string $status = 'active', string $name = 'Doctor'): void
    {
        DB::connection('central')->table('central_users')->insert([
            'user_id' => $id,
            'subject' => 'subject-'.$id,
            'email' => $email,
            'display_name' => $name,
            'status' => $status,
            'last_authenticated_at' => null,
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createCredential(string $userId, string $email, string $password): void
    {
        DB::connection('central')->table('auth_credentials')->insert([
            'user_id' => $userId,
            'email_normalized' => mb_strtolower(trim($email)),
            'password_hash' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function addMembership(
        string $userId,
        string $hospitalId,
        string $role,
        string $status = 'active',
        ?CarbonImmutable $validFrom = null,
        ?CarbonImmutable $validTo = null,
    ): string {
        $membershipId = (string) Str::uuid();

        DB::connection('central')->table('hospital_memberships')->insert([
            'membership_id' => $membershipId,
            'user_id' => $userId,
            'hospital_id' => $hospitalId,
            'role_code' => $role,
            'status' => $status,
            'valid_from' => $validFrom ?? now()->subDay(),
            'valid_to' => $validTo,
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $membershipId;
    }

    protected function login(
        string $email = 'doctor@example.test',
        string $password = 'secret-password',
        ?string $hospitalId = null,
    ): TestResponse {
        return $this->withHeaders(['X-Tenant-ID' => $hospitalId ?? $this->hospitalA])
            ->postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => $password,
            ]);
    }

    protected function tokenFrom(TestResponse $response): string
    {
        return (string) $response->json('access_token');
    }
}
