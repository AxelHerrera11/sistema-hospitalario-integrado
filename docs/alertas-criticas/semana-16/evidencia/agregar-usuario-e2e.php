<?php
// Uso: php add-user.php <correo> <rol>  (desde la raíz del worktree; solo bases *_e2e locales). Imprime el user_id.
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
str_ends_with((string) getenv('CENTRAL_DB_DATABASE'), '_e2e') || exit(3);
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
[$email, $role] = array_slice($argv, 1);
$id = (string) Str::uuid();
DB::connection('central')->table('central_users')->insert(['user_id' => $id, 'subject' => 'e2e-'.$id, 'email' => $email, 'display_name' => ucfirst($role).' Ficticio E2E']);
DB::connection('central')->table('auth_credentials')->insert(['user_id' => $id, 'email_normalized' => mb_strtolower($email), 'password_hash' => Hash::make((string) getenv('E2E_PASSWORD'))]);
DB::connection('central')->table('hospital_memberships')->insert(['user_id' => $id, 'hospital_id' => getenv('E2E_HOSPITAL_ID'), 'role_code' => $role, 'valid_from' => now()->subDay()]);
echo $id;
