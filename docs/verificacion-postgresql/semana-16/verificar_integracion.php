<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$required = [
    'database/migrations/hospital',
    'tests/Database/Hospital',
    'docs/verificacion-postgresql/estado-integracion-esquema.md',
    'docs/verificacion-postgresql/semana-15/README.md',
    'docs/verificacion-postgresql/semana-17/README.md',
    'docs/verificacion-postgresql/semana-18/README.md',
];

$expectedMigrations = [
    'medications' => '2026_08_11_000500_create_medication_tables.php',
    'lab_tests' => '2026_08_11_000600_create_lab_tests_table.php',
    'specimens' => '2026_08_11_000601_create_specimen_reception_tables.php',
    'critical_alerts' => '2026_08_11_000660_create_critical_alerts_table.php',
    'local_access_audits' => '2026_08_11_000700_create_audit_and_sync_tables.php',
    'prescriptions' => '2026_08_11_001000_create_prescription_tables.php',
    'lab_orders' => '2026_10_31_000001_create_lab_orders_tables.php',
];

$missing = [];
foreach ($required as $relative) {
    if (!file_exists($root . DIRECTORY_SEPARATOR . $relative)) $missing[] = $relative;
}

$migrationRoot = $root . '/database/migrations/hospital';
$migrationFiles = glob($migrationRoot . '/*.php') ?: [];
$migrationText = implode("\n", array_map(static fn (string $file): string => (string) file_get_contents($file), $migrationFiles));
foreach ($expectedMigrations as $table => $file) {
    if (!file_exists($migrationRoot . '/' . $file)) $missing[] = "migration {$file} ({$table})";
    if (!preg_match('/\\b' . preg_quote($table, '/') . '\\b/', $migrationText)) $missing[] = "table marker {$table}";
}

$postgresTests = glob($root . '/tests/Database/Hospital/*PostgresTest.php') ?: [];
if ($postgresTests === []) {
    $missing[] = 'tests/Database/Hospital/*PostgresTest.php';
}

if ($missing !== []) {
    fwrite(STDERR, "FALLA: contrato de verificación incompleto:\n");
    foreach ($missing as $item) {
        fwrite(STDERR, "- {$item}\n");
    }
    exit(1);
}

echo 'OK: migraciones HOSPITAL verificables (' . count($migrationFiles) . ")\n";
echo 'OK: pruebas PostgreSQL localizadas (' . count($postgresTests) . ")\n";
echo "Nota: la presencia de migraciones no acredita que una base PostgreSQL haya sido migrada.\n";
echo "Ejecute `php artisan migrate:status --database=hospital` y las pruebas *PostgresTest en un entorno PostgreSQL.\n";
