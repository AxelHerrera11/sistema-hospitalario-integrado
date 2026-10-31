<?php

declare(strict_types=1);

/**
 * Ayudante de pipeline-local.sh para reproducir los servicios PostgreSQL del workflow (puertos 55431 y 55433,
 * usuario y contraseña «postgres», solo bases desechables *_test y *_e2e). Usa PDO: no requiere psql.
 *
 *   php postgres-ci-local.php check                       # 0 si ambos puertos aceptan conexiones
 *   php postgres-ci-local.php ensure <puerto> <base> [extensión]
 *   php postgres-ci-local.php reset <puerto> <base_e2e>       # borra y crea de nuevo (solo *_e2e)
 */
$connect = static fn (int $port, string $db = 'postgres'): PDO => new PDO(
    "pgsql:host=127.0.0.1;port={$port};dbname={$db}",
    'postgres',
    'postgres',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
);

try {
    switch ($argv[1] ?? '') {
        case 'check':
            $connect(55431);
            $connect(55433);
            exit(0);
        case 'reset':
            [$port, $db] = [(int) ($argv[2] ?? 0), (string) ($argv[3] ?? '')];
            if (! in_array($port, [55431, 55433], true) || preg_match('/^his_(central|hospital)_e2e$/D', $db) !== 1) {
                throw new InvalidArgumentException("Destino no permitido: {$db}@{$port}");
            }
            $admin = $connect($port);
            $admin->exec("DROP DATABASE IF EXISTS {$db} WITH (FORCE)");
            $admin->exec("CREATE DATABASE {$db}");
            exit(0);
        case 'ensure':
            [$port, $db, $extension] = [(int) ($argv[2] ?? 0), (string) ($argv[3] ?? ''), $argv[4] ?? null];
            // Nunca toca una base que no sea desechable ni un puerto distinto de los del workflow.
            if (! in_array($port, [55431, 55433], true) || preg_match('/^his_(central|hospital)_(test|e2e)$/D', $db) !== 1) {
                throw new InvalidArgumentException("Destino no permitido: {$db}@{$port}");
            }
            $admin = $connect($port);
            $exists = $admin->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
            $exists->execute([$db]);
            if ($exists->fetchColumn() === false) {
                $admin->exec("CREATE DATABASE {$db}");
            }
            if (is_string($extension) && preg_match('/^[a-z_]+$/D', $extension) === 1) {
                $connect($port, $db)->exec("CREATE EXTENSION IF NOT EXISTS {$extension}");
            }
            exit(0);
        default:
            fwrite(STDERR, "Uso: php postgres-ci-local.php check | ensure <puerto> <base> [extensión] | reset <puerto> <base_e2e>\n");
            exit(2);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'postgres-ci-local: '.$e->getMessage().PHP_EOL);
    exit(1);
}
