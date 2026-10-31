<?php

declare(strict_types=1);

/**
 * Mide RNF-MED-04 (ASII-04): consulta de disponibilidad ≤ 500 ms (p95) para 20 médicos y 14 días.
 *
 * Siembra datos FICTICIOS en una base HOSPITAL vacía y ya migrada (nunca usar una base con datos reales) y mide el
 * `Router` del micro-HIS contra PostgreSQL, sin HTTP ni Laravel: mide la consulta y el cálculo de franjas, no la red.
 *
 *   php artisan migrate:fresh --database=hospital --path=database/migrations/hospital --realpath --force
 *   php -d extension=pdo_pgsql docs/medicos-especialidades/semana-18/evidencia/medir-rendimiento.php
 *
 * Conexión: HOSPITAL_DB_HOST/PORT/DATABASE/USERNAME/PASSWORD, todas obligatorias: sin valores por defecto, para no sembrar
 * por error en una base compartida (por ejemplo, la de otra persona en 127.0.0.1:55433).
 * Para no tocar una base por error, exige un nombre que termine en `_test` o `_e2e`.
 * Salida: tabla Markdown por stdout; código de salida 1 si el p95 supera 500 ms.
 */

use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Persistence\PdoScheduleProvider;
use MicroHis\Medicos\Presentation\Request;

require __DIR__ . '/../../../../micro-his/medicos-especialidades/app.php';

const DOCTORS = 20;
const DAYS = 14;
const REPETITIONS = 30;
const LIMIT_MS = 500.0;
const FIRST_DAY = '2026-10-19';

$env = static function (string $name): string {
    $value = getenv($name);
    if (! is_string($value) || $value === '') {
        fwrite(STDERR, "Falta $name: la conexión debe indicarse explícitamente.\n");
        exit(2);
    }
    return $value;
};
$database = $env('HOSPITAL_DB_DATABASE');
if (preg_match('/_(test|e2e)$/', $database) !== 1) {
    fwrite(STDERR, "Se niega a sembrar en «$database»: el nombre debe terminar en _test o _e2e.\n");
    exit(2);
}
$pdo = new PDO(
    sprintf('pgsql:host=%s;port=%s;dbname=%s', $env('HOSPITAL_DB_HOST'), $env('HOSPITAL_DB_PORT'), $database),
    $env('HOSPITAL_DB_USERNAME'),
    $env('HOSPITAL_DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
if ((int) $pdo->query('SELECT count(*) FROM doctor_profiles')->fetchColumn() !== 0) {
    fwrite(STDERR, "La base ya tiene médicos: migre de nuevo (migrate:fresh) antes de medir.\n");
    exit(2);
}

/* ----------------------------------------------------------------- siembra */
$zone = 'America/Guatemala';
$pdo->beginTransaction();
$pdo->exec("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
    SELECT gen_random_uuid(), gen_random_uuid(), 'Médico ficticio ' || g, 'medico', now() FROM generate_series(1, " . DOCTORS . ') g');
$pdo->exec("INSERT INTO specialties (code, name) VALUES ('NEURO', 'Neurología')");
$pdo->exec("INSERT INTO doctor_profiles (local_user_ref_id, license_number, valid_from)
    SELECT local_user_ref_id, 'MED-' || row_number() OVER (ORDER BY local_user_ref_id), '2026-01-01' FROM local_user_refs");
$pdo->exec("INSERT INTO doctor_specialties (doctor_profile_id, specialty_id, is_primary, valid_from)
    SELECT p.doctor_profile_id, s.specialty_id, true, '2026-01-01' FROM doctor_profiles p CROSS JOIN specialties s");
// Mañana 08:00-12:00 y tarde 14:00-18:00, los siete días, en bloques de 30 minutos.
$pdo->exec("INSERT INTO doctor_schedules (doctor_profile_id, specialty_id, weekday, start_minute, end_minute, slot_minutes, valid_from)
    SELECT p.doctor_profile_id, s.specialty_id, w, b.a, b.b, 30, '2026-01-01'
    FROM doctor_profiles p CROSS JOIN specialties s CROSS JOIN generate_series(1, 7) w CROSS JOIN (VALUES (480, 720), (840, 1080)) b(a, b)");
// Cuatro citas por médico y día, de 30 minutos, separadas 90 minutos; cada una con su paciente ficticio.
$pdo->exec('CREATE TEMP TABLE agenda AS SELECT p.doctor_profile_id, d, k, gen_random_uuid() AS patient_id
    FROM doctor_profiles p CROSS JOIN generate_series(0, ' . (DAYS - 1) . ') d CROSS JOIN generate_series(0, 3) k');
$pdo->exec("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex)
    SELECT patient_id, gen_random_uuid(), 'PAC-' || row_number() OVER (), 'Paciente', 'Ficticio', '1990-01-01', 'unknown' FROM agenda");
$pdo->exec("INSERT INTO appointments (local_patient_id, doctor_profile_id, specialty_id, starts_at, ends_at, status, reason)
    SELECT a.patient_id, a.doctor_profile_id, (SELECT specialty_id FROM specialties LIMIT 1),
        ((date '" . FIRST_DAY . "' + a.d) + time '08:00' + a.k * interval '90 minutes') AT TIME ZONE '$zone',
        ((date '" . FIRST_DAY . "' + a.d) + time '08:30' + a.k * interval '90 minutes') AT TIME ZONE '$zone', 'scheduled', 'Consulta' FROM agenda a");
$pdo->commit();
$appointments = (int) $pdo->query('SELECT count(*) FROM appointments')->fetchColumn();

/* ----------------------------------------------------------------- medición */
$store = new PdoDoctorStore($pdo, $zone);
$router = medicos_router($store, $store, new PdoScheduleProvider($pdo));
$query = static function (string $date) use ($router): array {
    $start = hrtime(true);
    $response = $router->dispatch(new Request('GET', '/availability', ['X-User-ID' => 'medicion'], ['specialty' => 'NEURO', 'date' => $date], []));
    $ms = (hrtime(true) - $start) / 1e6;
    $body = json_decode($response['body'], true);
    if ($response['status'] !== 200 || count($body['doctors']) !== DOCTORS) {
        fwrite(STDERR, "Respuesta inesperada ({$response['status']}) para $date\n");
        exit(2);
    }
    return [$ms, $body];
};
$percentile = static function (array $values, float $p): float {
    sort($values);
    return $values[(int) max(0, ceil($p * count($values)) - 1)];
};
$days = array_map(static fn (int $i): string => date('Y-m-d', strtotime(FIRST_DAY . " +$i day")), range(0, DAYS - 1));

$query($days[0]); // calentamiento: abre la conexión y prepara el plan
[, $sample] = $query($days[0]);
$free = array_sum(array_map(static fn (array $d): int => count($d['slots']), $sample['doctors']));

$single = $window = [];
for ($r = 0; $r < REPETITIONS; $r++) {
    $total = 0.0;
    foreach ($days as $day) {
        [$ms] = $query($day);
        $single[] = $ms;
        $total += $ms;
    }
    $window[] = $total;
}

$p95 = $percentile($single, 0.95);
$p95Window = $percentile($window, 0.95);
printf("| Medida | Valor |\n|---|---|\n");
printf("| Datos | %d médicos, %d días, %d citas, %d franjas libres en el día de muestra |\n", DOCTORS, DAYS, $appointments, $free);
printf("| Consultas de un día | %d (%d repeticiones × %d días) |\n", count($single), REPETITIONS, DAYS);
printf("| Un día: mediana / p95 / máximo | %.1f / %.1f / %.1f ms |\n", $percentile($single, 0.5), $p95, max($single));
printf("| Ventana de 14 días (14 consultas seguidas): mediana / p95 / máximo | %.1f / %.1f / %.1f ms |\n", $percentile($window, 0.5), $p95Window, max($window));
printf("| Límite de RNF-MED-04 | %.0f ms (p95) |\n", LIMIT_MS);
printf("| Resultado | %s |\n", $p95 <= LIMIT_MS && $p95Window <= LIMIT_MS ? 'Cumple (un día y ventana de 14 días)' : ($p95 <= LIMIT_MS ? 'Cumple para un día; la ventana de 14 días lo supera' : 'No cumple'));
exit($p95 <= LIMIT_MS ? 0 : 1);
