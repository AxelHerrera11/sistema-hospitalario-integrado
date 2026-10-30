<?php

declare(strict_types=1);

/**
 * Pruebas de SEGURIDAD (Semana 17): matriz de permisos rol × operación, datos sensibles en respuestas y en la
 * bitácora, entradas hostiles y aislamiento entre hospitales. Cada prueba se cruza con una amenaza de
 * docs/expedientes-medicos/semana-17/matriz-amenazas.md (columna «Prueba»).
 */
echo PHP_EOL . '== Seguridad (permisos, datos sensibles, entradas hostiles)' . PHP_EOL;

const AJENO = '99999999-9999-4999-8999-999999999999';
const ROLES = ['DRA_LOPEZ' => DRA_LOPEZ, 'DR_PEREZ' => DR_PEREZ, 'ENF_RUIZ' => ENF_RUIZ, 'RECEP_MORA' => RECEP_MORA, 'ADMIN_SOL' => ADMIN_SOL, 'AJENO' => AJENO];

// S-01 Matriz ACTIVA (config/rbac.php): solo la médica con vínculo consulta; nadie puede abrir.
['router' => $router] = make_app();
$matrix = [];
foreach (ROLES as $name => $user) {
    $matrix[$name] = [code(call($router, 'GET', 'P-0001', $user)), code(call($router, 'POST', 'P-0002', $user))];
}
check('S-01 matriz activa: GET 200 solo para la médica tratante; POST 403 para todos', $matrix === [
    'DRA_LOPEZ' => ['200 ', '403 ACCESO_DENEGADO'],
    'DR_PEREZ' => ['403 SIN_VINCULO_ASISTENCIAL', '403 ACCESO_DENEGADO'],
    'ENF_RUIZ' => ['403 SIN_VINCULO_ASISTENCIAL', '403 ACCESO_DENEGADO'],
    'RECEP_MORA' => ['403 ACCESO_DENEGADO', '403 ACCESO_DENEGADO'],
    'ADMIN_SOL' => ['403 ACCESO_DENEGADO', '403 ACCESO_DENEGADO'],
    'AJENO' => ['403 ACCESO_DENEGADO', '403 ACCESO_DENEGADO'],
]);

// S-02 Con la concesión PROPUESTA, abren medicina y recepción; enfermería, administración y ajenos siguen sin poder.
['router' => $router] = make_app(proposed: true);
$open = array_map(static fn (string $user): int => call($router, 'POST', 'P-0002', $user)['status'], ROLES);
check('S-02 apertura propuesta: médicos y recepción 201/200; enfermería, admin y ajeno 403', $open === [
    'DRA_LOPEZ' => 201, 'DR_PEREZ' => 200, 'ENF_RUIZ' => 403, 'RECEP_MORA' => 200, 'ADMIN_SOL' => 403, 'AJENO' => 403,
]);

// S-03 Sin enumeración: paciente inexistente y paciente sin vínculo responden igual.
['router' => $router] = make_app();
check('S-03 P-9999 (no existe) y P-0003 (existe, sin vínculo) → mismo 403 SIN_VINCULO_ASISTENCIAL', code(call($router, 'GET', 'P-9999')) === code(call($router, 'GET', 'P-0003'))
    && code(call($router, 'GET', 'P-9999')) === '403 SIN_VINCULO_ASISTENCIAL');

// S-04 Entradas hostiles en el código: se rechazan antes de llegar a la base, sin filtrar detalles.
$hostile = ["P-0001' OR '1'='1", '../../etc/passwd', str_repeat('P', 300), "P-0001\0", '<script>alert(1)</script>'];
$answers = array_map(static fn (string $c): array => call($router, 'GET', $c), $hostile);
check('S-04 inyección, rutas, 300 caracteres, byte nulo y HTML → 422 CODIGO_PACIENTE_INVALIDO sin SQL ni eco', array_filter($answers, static fn (array $a): bool => code($a) !== '422 CODIGO_PACIENTE_INVALIDO'
    || preg_match('/SQL|PDO|select|script|passwd/i', $a['raw']) === 1) === []);

// S-05 Aislamiento entre hospitales: otro hospital, hospital inexistente y sin hospital nunca devuelven datos.
$tenants = [code(call($router, 'GET', 'P-0001', DRA_LOPEZ, OTRO_HOSPITAL)), code(call($router, 'GET', 'P-0001', DRA_LOPEZ, 'hospital-x')), code(call($router, 'GET', 'P-0001', DRA_LOPEZ, null)), code(call($router, 'GET', 'P-0001', ENF_RUIZ, OTRO_HOSPITAL))];
check('S-05 hospital-norte 503, inexistente 403, ausente 400 y no miembro 403', $tenants === ['503 HOSPITAL_NO_DISPONIBLE', '403 ACCESO_DENEGADO', '400 TENANT_REQUERIDO', '403 ACCESO_DENEGADO']);

// S-06 Datos sensibles en TODAS las respuestas: sin contacto, global_id ni SQL; siempre no-store y nosniff.
['router' => $router] = make_app('pdo', proposed: true);
$all = [];
foreach (ROLES as $user) {
    foreach (['GET' => 'P-0001', 'POST' => 'P-0002'] as $method => $patient) {
        $all[] = call($router, $method, $patient, $user);
    }
}
$leaks = array_filter($all, static fn (array $r): bool => preg_match('/contact|global_id|f0000000-|SQLSTATE|password/i', $r['raw']) === 1
    || $r['headers']['Cache-Control'] !== 'no-store' || $r['headers']['X-Content-Type-Options'] !== 'nosniff');
check('S-06 ' . count($all) . ' respuestas (6 actores × consulta/apertura): sin contacto, global_id ni SQL; no-store + nosniff', $leaks === []);

// S-07 La bitácora identifica quién, qué y resultado, pero no guarda datos clínicos ni nombres del paciente.
['router' => $router, 'audit' => $audit] = make_app('pdo', proposed: true);
foreach (ROLES as $user) {
    call($router, 'GET', 'P-0001', $user);
    call($router, 'POST', 'P-0002', $user);
}
$serialized = json_encode(array_map(static fn ($e): array => get_object_vars($e), $audit->events), JSON_UNESCAPED_UNICODE);
check('S-07 ' . count($audit->events) . ' eventos de auditoría sin motivos de episodio, nombres ni fecha de nacimiento', count($audit->events) === 12
    && preg_match('/Neumon|Dolor|Control de presi|Ana|Gómez|Luis|1980-05-14|1992-11-03/u', (string) $serialized) === 0);

// S-08 Repetir la apertura con la bitácora caída: no se entrega el número del expediente (503, como la consulta).
['router' => $router] = make_app(proposed: true, auditDown: true);
$down = call($router, 'POST', 'P-0001', RECEP_MORA);
check('S-08 apertura repetida sin bitácora → 503 AUDITORIA_NO_DISPONIBLE y sin EXP- en la respuesta', code($down) === '503 AUDITORIA_NO_DISPONIBLE'
    && !str_contains($down['raw'], 'EXP-'));
