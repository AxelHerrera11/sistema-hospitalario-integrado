<?php

declare(strict_types=1);

// RNF-TA-04 con conexiones independientes: dos procesos PHP, cada uno con su conexión PostgreSQL, compiten a la vez.
// Uso: TRASLADOS_PG_DSN="pgsql:...;dbname=<base>_test" TRASLADOS_PG_PASSWORD=... php micro-his/traslados-altas/tests/concurrencia.php [rondas]
// C1: la misma estancia hacia dos camas distintas → como máximo un éxito; el otro, ESTANCIA_NO_VIGENTE.
// C2: dos episodios distintos hacia la misma cama → como máximo un éxito; el otro, DESTINO_OCUPADO.

require __DIR__ . '/harness.php';

$dsn = (string) getenv('TRASLADOS_PG_DSN');
if (preg_match('/dbname=\w+_test(;|$)/', $dsn) !== 1) {
    fwrite(STDERR, "TRASLADOS_PG_DSN debe apuntar a una base desechable cuyo nombre termine en _test.\n");
    exit(2);
}
$rondas = (int) ($argv[1] ?? 5);
$pdo = new PDO($dsn, getenv('TRASLADOS_PG_USER') ?: 'postgres', (string) getenv('TRASLADOS_PG_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$valor = static function (string $sql, array $p = []) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($p);

    return $s->fetchColumn();
};
$actor = $valor("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
    VALUES (gen_random_uuid(), gen_random_uuid(), 'Enfermera de prueba', 'enfermeria', now()) RETURNING local_user_ref_id");
$internar = static function (string $cama) use ($valor, $actor): array {
    $paciente = $valor("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
        VALUES (gen_random_uuid(), :c, 'Paciente', 'Ficticio', '1990-01-01', 'unknown') RETURNING local_patient_id", ['c' => 'CC-' . bin2hex(random_bytes(5))]);
    $episodio = $valor("INSERT INTO care_episodes (local_patient_id, type, started_at, reason) VALUES (:p, 'inpatient', now() - interval '2 days', 'Ingreso')
        RETURNING care_episode_id", ['p' => $paciente]);

    return [$episodio, $valor("INSERT INTO bed_stays (care_episode_id, bed_id, assigned_by_user_ref_id, started_at, reason)
        VALUES (:e, :b, :a, now() - interval '1 day', 'Ingreso') RETURNING bed_stay_id", ['e' => $episodio, 'b' => $cama, 'a' => $actor])];
};
$camas = static function (int $n) use ($valor): array {
    $sala = $valor('INSERT INTO wards (code, name, location) VALUES (:c, :n, :l) RETURNING ward_id', ['c' => 'CC-' . bin2hex(random_bytes(4)), 'n' => 'Sala', 'l' => 'Piso']);

    return array_map(static fn (int $i) => $valor('INSERT INTO beds (ward_id, code, type) VALUES (:w, :c, :t) RETURNING bed_id',
        ['w' => $sala, 'c' => "K{$i}", 't' => 'general']), range(1, $n));
};

/** Lanza dos hijos detenidos en la barrera, los suelta a la vez y devuelve sus resultados. */
$competir = static function (array $a, array $b) use ($pdo, $actor): array {
    $clave = random_int(1, PHP_INT_MAX);
    $pdo->prepare('SELECT pg_advisory_lock(CAST(:k AS bigint))')->execute(['k' => $clave]);
    $hijos = [];
    foreach ([$a, $b] as [$estancia, $cama]) {
        $comando = [PHP_BINARY, __DIR__ . '/hijo-concurrencia.php', (string) $clave, $actor, $estancia, $cama];
        $hijos[] = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubos);
        $salidas[] = $tubos;
    }
    // Espera a que ambos estén bloqueados en la barrera antes de soltarlos.
    $espera = $pdo->prepare("SELECT count(*) FROM pg_locks WHERE locktype = 'advisory' AND NOT granted AND objid = CAST(:k AS bigint) % 4294967296");
    for ($i = 0; $i < 100; $i++) {
        $espera->execute(['k' => $clave]);
        if ((int) $espera->fetchColumn() === 2) {
            break;
        }
        usleep(50000);
    }
    $pdo->prepare('SELECT pg_advisory_unlock(CAST(:k AS bigint))')->execute(['k' => $clave]);
    $resultados = [];
    foreach ($hijos as $i => $hijo) {
        $resultados[] = json_decode((string) stream_get_contents($salidas[$i][1]), true) ?? ['resultado' => 'ERROR', 'detalle' => stream_get_contents($salidas[$i][2])];
        proc_close($hijo);
    }

    return $resultados;
};
$resumen = static fn (array $r): string => implode(' + ', array_map(static fn (array $x): string => $x['resultado'] . ($x['motivo'] ? "({$x['motivo']})" : ''), $r));

for ($ronda = 1; $ronda <= $rondas; $ronda++) {
    [$a, $b, $c] = $camas(3);
    [$episodio, $estancia] = $internar($a);
    $r = $competir([$estancia, $b], [$estancia, $c]);
    $exitos = array_filter($r, static fn (array $x): bool => $x['resultado'] === 'ExitoConfirmado');
    $otros = array_column(array_filter($r, static fn (array $x): bool => $x['resultado'] !== 'ExitoConfirmado'), 'motivo');
    $abiertas = (int) $valor('SELECT count(*) FROM bed_stays WHERE care_episode_id = :e AND ended_at IS NULL', ['e' => $episodio]);
    check("C1 ronda {$ronda}: misma estancia a dos camas → un éxito y ESTANCIA_NO_VIGENTE; una sola estancia abierta [{$resumen($r)}]",
        count($exitos) === 1 && $otros === ['ESTANCIA_NO_VIGENTE'] && $abiertas === 1);

    [$a1, $a2, $d] = $camas(3);
    [, $estancia1] = $internar($a1);
    [, $estancia2] = $internar($a2);
    $r = $competir([$estancia1, $d], [$estancia2, $d]);
    $exitos = array_filter($r, static fn (array $x): bool => $x['resultado'] === 'ExitoConfirmado');
    $otros = array_column(array_filter($r, static fn (array $x): bool => $x['resultado'] !== 'ExitoConfirmado'), 'motivo');
    $ocupantes = (int) $valor('SELECT count(*) FROM bed_stays WHERE bed_id = :d AND ended_at IS NULL', ['d' => $d]);
    check("C2 ronda {$ronda}: dos episodios a la misma cama → un éxito y DESTINO_OCUPADO; un solo ocupante [{$resumen($r)}]",
        count($exitos) === 1 && $otros === ['DESTINO_OCUPADO'] && $ocupantes === 1);
}

finish();
