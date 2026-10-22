<?php

declare(strict_types=1);

/**
 * Siembra datos FICTICIOS en la base CENTRAL efímera del job e2e de CI.
 * No crea ni altera tablas: solo inserta filas en el esquema que dejan las
 * migraciones del repositorio. Se niega a correr fuera de un ambiente aislado.
 *
 *   E2E_ALLOW_SEED=1 APP_ENV=e2e CENTRAL_DB_DATABASE=his_central_e2e ... php tests/E2E/fixtures/seed-e2e.php
 *
 * Variables: E2E_PASSWORD (generada por ejecución), E2E_HOSPITAL_ID, E2E_HOSPITAL_2_ID,
 * E2E_ADMIN_EMAIL, E2E_MEDICO_EMAIL. Pendiente: reemplazar por el seeder común del grupo
 * (acuerdo "datos semilla comunes", Cindy y Madelin) cuando exista.
 */
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$guards = [
    'E2E_ALLOW_SEED=1' => getenv('E2E_ALLOW_SEED') === '1',
    'APP_ENV=e2e' => getenv('APP_ENV') === 'e2e',
    'CENTRAL_DB_DATABASE termina en _e2e' => str_ends_with((string) getenv('CENTRAL_DB_DATABASE'), '_e2e'),
    'CENTRAL_DB_HOST local' => in_array(getenv('CENTRAL_DB_HOST'), ['127.0.0.1', 'localhost'], true),
];
foreach ($guards as $rule => $ok) {
    if (! $ok) {
        fwrite(STDERR, "seed-e2e: rechazado, no se cumple {$rule}. Nunca se siembra una base compartida o productiva.\n");
        exit(3);
    }
}
$password = (string) getenv('E2E_PASSWORD');
if (strlen($password) < 16) {
    fwrite(STDERR, "seed-e2e: E2E_PASSWORD debe tener al menos 16 caracteres (se genera en cada ejecución).\n");
    exit(3);
}

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$db = DB::connection('central');
$network = (string) Str::uuid();
$hospitals = [(string) getenv('E2E_HOSPITAL_ID') => 'Hospital Ficticio E2E Uno', (string) getenv('E2E_HOSPITAL_2_ID') => 'Hospital Ficticio E2E Dos'];
$users = [
    (string) getenv('E2E_ADMIN_EMAIL') => ['Admin Ficticio E2E', 'admin_hospital'],
    (string) getenv('E2E_MEDICO_EMAIL') => ['Medico Ficticio E2E', 'medico'],
];

$db->transaction(function () use ($db, $network, $hospitals, $users, $password): void {
    $db->table('networks')->insert(['network_id' => $network, 'name' => 'Red ficticia E2E', 'timezone' => 'UTC']);
    foreach ($hospitals as $id => $name) {
        Str::isUuid($id) || throw new InvalidArgumentException('E2E_HOSPITAL_ID/E2E_HOSPITAL_2_ID deben ser UUID.');
        $db->table('hospitals')->insert(['hospital_id' => $id, 'network_id' => $network, 'code' => 'E2E-'.substr($id, -4), 'name' => $name, 'timezone' => 'UTC']);
    }
    $first = array_key_first($hospitals);
    foreach ($users as $email => [$name, $role]) {
        $userId = (string) Str::uuid();
        $db->table('central_users')->insert(['user_id' => $userId, 'subject' => 'e2e-'.$userId, 'email' => $email, 'display_name' => $name]);
        $db->table('auth_credentials')->insert(['user_id' => $userId, 'email_normalized' => mb_strtolower($email), 'password_hash' => Hash::make($password)]);
        $db->table('hospital_memberships')->insert(['user_id' => $userId, 'hospital_id' => $first, 'role_code' => $role, 'valid_from' => now()->subDay()]);
    }
});

echo 'seed-e2e: '.count($hospitals).' hospitales y '.count($users)." usuarios ficticios creados.\n";
