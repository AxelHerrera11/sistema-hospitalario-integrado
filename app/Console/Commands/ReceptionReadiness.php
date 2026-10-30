<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReceptionReadiness extends Command
{
    protected $signature = 'reception:readiness {--json}';
    protected $description = 'Verifica configuración e integración clínica sin divulgar datos de pacientes';

    public function handle(): int
    {
        $checks = ['debug_disabled' => !config('app.debug'),
            'hospital_configured' => Str::isUuid((string) config('reception.hospital_id')),
            'app_key_configured' => strlen((string) config('app.key')) >= 32,
            'jwt_configured' => strlen((string) config('jwt.secret')) >= 32,
            'vue_built' => is_file(public_path('build/manifest.json')),
            'module_installed' => class_exists(\App\Modules\Reception\Application\ReceiveSpecimen::class)];
        foreach (['central' => ['hospitals', 'hospital_memberships', 'auth_sessions', 'audit_log'],
            'hospital' => ['local_patients', 'care_episodes', 'local_user_refs', 'specimens', 'reception_order_items', 'outbox_events']] as $connection => $tables) {
            try {
                $db = DB::connection($connection);
                $checks[$connection.'_postgresql'] = $db->getDriverName() === 'pgsql'
                    && (int) $db->scalar('SHOW server_version_num') >= 150000;
                foreach ($tables as $table) $checks[$connection.'_'.$table] = $db->getSchemaBuilder()->hasTable($table);
            } catch (\Throwable) { $checks[$connection.'_available'] = false; }
        }
        if (($checks['central_hospitals'] ?? false) && $checks['hospital_configured']) {
            $checks['hospital_active'] = DB::connection('central')->table('hospitals')
                ->where('hospital_id', config('reception.hospital_id'))->where('status', 'active')->exists();
        }
        $ready = !in_array(false, $checks, true);
        $result = ['ready' => $ready, 'checks' => $checks];
        if ($this->option('json')) $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        else foreach ($checks as $name => $passed) $this->line(($passed ? 'PASS ' : 'FAIL ').$name);
        return $ready ? self::SUCCESS : self::FAILURE;
    }
}
