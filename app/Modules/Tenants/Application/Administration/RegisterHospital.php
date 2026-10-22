<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Application\Administration;

use App\Modules\Tenants\Domain\Administration\AdminFailure;
use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterHospital
{
    public function __construct(private readonly AuditLogRepositoryInterface $audit) {}

    public function execute(
        string $actorId,
        string $actorHospitalId,
        string $networkId,
        string $code,
        string $name,
        string $timezone,
        ?string $traceId = null,
    ): array {
        $traceId ??= 'trace-'.bin2hex(random_bytes(6));
        $hospitalId = (string) Str::uuid();

        DB::connection('central')->transaction(function () use (
            $actorId,
            $actorHospitalId,
            $networkId,
            $code,
            $name,
            $timezone,
            $traceId,
            $hospitalId,
        ): void {
            $central = DB::connection('central');

            if (! $central->table('networks')->where('network_id', $networkId)->exists()) {
                throw AdminFailure::networkNotFound();
            }

            if ($central->table('hospitals')
                ->where('network_id', $networkId)
                ->where('code', $code)
                ->exists()) {
                throw AdminFailure::hospitalCodeConflict();
            }

            $central->table('hospitals')->insert([
                'hospital_id' => $hospitalId,
                'network_id' => $networkId,
                'code' => $code,
                'name' => $name,
                'status' => 'inactive',
                'timezone' => $timezone,
                'clinical_endpoint' => null,
                'schema_version' => '1',
                'metadata' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->logRaw('tenant.created', [
                'user_id' => $actorId,
                'hospital_id' => $actorHospitalId,
                'tenant_id' => $actorHospitalId,
                'target_hospital_id' => $hospitalId,
                'network_id' => $networkId,
                'permission' => 'tenants.crear',
                'reason' => 'hospital_registered_pending_provisioning',
                'result' => 'success',
                'trace_id' => $traceId,
            ], 'info');
        });

        return [
            'hospital_id' => $hospitalId,
            'network_id' => $networkId,
            'code' => $code,
            'name' => $name,
            'status' => 'inactive',
            'provisioning_required' => true,
        ];
    }
}
