<?php

use App\Modules\Tenants\Infrastructure\Audit\AuditLogRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.connections.central' => json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR)]);
DB::connection('central')->select("SELECT set_config('application_name', ?, false)", [$argv[2]]);
DB::connection('central')->statement("SET statement_timeout = '15s'");
(new AuditLogRepository)->logRaw('test.second', ['hospital_id' => 'hospital-b']);
echo 'written';
