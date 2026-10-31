<?php

declare(strict_types=1);

use MicroHis\ExpedienteMedico\Presentation\Request;

/**
 * Pruebas de CAJA NEGRA por HTTP (Router → JSON), sin mirar el código: clases de equivalencia, límites, permisos
 * por rol con la matriz REAL de config/rbac.php y errores. Se repiten con los adaptadores PDO (SQLite) e InMemory.
 */
echo PHP_EOL . '== Caja negra (contrato HTTP)' . PHP_EOL;

foreach (['pdo', 'memoria'] as $adapter) {
    $t = static fn (string $label): string => "[$adapter] $label";

    // N-01 Clase válida: médica con vínculo asistencial consulta el expediente longitudinal.
    ['router' => $router, 'audit' => $audit] = make_app($adapter);
    $ok = call($router, 'GET', 'P-0001');
    check($t('N-01 médica tratante → 200, 3 episodios (reciente primero) y Cache-Control no-store'), $ok['status'] === 200
        && $ok['body']['record']['record_number'] === 'EXP-P-0001' && count($ok['body']['episodes']) === 3
        && $ok['body']['episodes'][0]['type'] === 'inpatient' && $ok['body']['summary'] === ['episodes' => 3, 'open_episodes' => 1]
        && $ok['headers']['Cache-Control'] === 'no-store' && !array_key_exists('contact', $ok['body']['patient']));

    // N-02..N-04 Permisos por rol (matriz activa): enfermería tiene el permiso pero no vínculo; recepción y admin no tienen permiso.
    $nurse = call($router, 'GET', 'P-0001', ENF_RUIZ);
    check($t('N-02 enfermería (expediente.ver, sin asignación) → 403 SIN_VINCULO_ASISTENCIAL'), code($nurse) === '403 SIN_VINCULO_ASISTENCIAL');
    $reception = call($router, 'GET', 'P-0001', RECEP_MORA);
    check($t('N-03 recepción sin expediente.ver → 403 ACCESO_DENEGADO con trace_id'), code($reception) === '403 ACCESO_DENEGADO'
        && str_starts_with((string) ($reception['body']['trace_id'] ?? ''), 'trace-'));
    check($t('N-04 admin_hospital sin expediente.ver → 403 ACCESO_DENEGADO'), code(call($router, 'GET', 'P-0001', ADMIN_SOL)) === '403 ACCESO_DENEGADO');
    check($t('N-04b ninguna respuesta denegada contiene datos del paciente'), !str_contains($nurse['raw'] . $reception['raw'], 'Gómez')
        && !str_contains($nurse['raw'] . $reception['raw'], 'Neumon'));

    // N-05 Aislamiento por tenant.
    check($t('N-05 usuario sin membresía en el hospital indicado → 403'), code(call($router, 'GET', 'P-0001', ENF_RUIZ, OTRO_HOSPITAL)) === '403 ACCESO_DENEGADO');
    check($t('N-05b membresía válida pero hospital sin base configurada → 503 HOSPITAL_NO_DISPONIBLE'), code(call($router, 'GET', 'P-0001', DRA_LOPEZ, OTRO_HOSPITAL)) === '503 HOSPITAL_NO_DISPONIBLE');

    // N-06 Identidad y tenant obligatorios.
    check($t('N-06 sin X-User-ID → 401 IDENTIDAD_REQUERIDA'), code(call($router, 'GET', 'P-0001', null)) === '401 IDENTIDAD_REQUERIDA');
    check($t('N-06b sin X-Tenant-ID → 400 TENANT_REQUERIDO'), code(call($router, 'GET', 'P-0001', DRA_LOPEZ, null)) === '400 TENANT_REQUERIDO');

    // N-07 Valores límite del código de paciente (1..40).
    check($t('N-07 código de 1 carácter (válido, inexistente) → 403 sin revelar existencia'), code(call($router, 'GET', 'P')) === '403 SIN_VINCULO_ASISTENCIAL');
    check($t('N-07b código de 40 caracteres (válido) → 403'), code(call($router, 'GET', str_repeat('P', 40))) === '403 SIN_VINCULO_ASISTENCIAL');
    check($t('N-07c código de 41 caracteres → 422 CODIGO_PACIENTE_INVALIDO'), code(call($router, 'GET', str_repeat('P', 41))) === '422 CODIGO_PACIENTE_INVALIDO');
    check($t("N-07d código con inyección SQL → 422"), code(call($router, 'GET', "P-0001' OR '1'='1")) === '422 CODIGO_PACIENTE_INVALIDO');

    // N-08 Apertura con la matriz REAL: expediente.abrir está activo para medicina, no para recepción.
    check($t('N-08 apertura con la matriz activa → 403 para recepción, 201 para medicina'), code(call($router, 'POST', 'P-0002', RECEP_MORA)) === '403 ACCESO_DENEGADO'
        && code(call($router, 'POST', 'P-0002', DRA_LOPEZ)) === '201 ');

    // N-09..N-10 Apertura con la concesión PROPUESTA activada solo en esta prueba.
    ['router' => $router, 'audit' => $audit] = make_app($adapter, proposed: true);
    $created = call($router, 'POST', 'P-0002', RECEP_MORA);
    $repeat = call($router, 'POST', 'P-0002', RECEP_MORA);
    check($t('N-09 apertura → 201 con EXP-P-0002 y sin datos del paciente; repetir → 200 created=false (idempotente)'), $created['status'] === 201
        && $created['body']['record_number'] === 'EXP-P-0002' && !isset($created['body']['patient']) && $repeat['status'] === 200
        && $repeat['body']['created'] === false && $repeat['body']['medical_record_id'] === $created['body']['medical_record_id']);
    $after = call($router, 'GET', 'P-0002');
    check($t('N-09b tras abrir, la médica tratante consulta el expediente nuevo (1 episodio abierto)'), $after['status'] === 200 && $after['body']['summary']['open_episodes'] === 1);
    check($t('N-10 apertura de paciente fallecido → 409; inexistente → 404'), code(call($router, 'POST', 'P-0003', RECEP_MORA)) === '409 PACIENTE_NO_ACTIVO'
        && code(call($router, 'POST', 'P-9999', RECEP_MORA)) === '404 PACIENTE_NO_ENCONTRADO');
    check($t('N-10b la auditoría registró abierto + consultado'), array_values(array_intersect($audit->names(), ['expediente_medico.abierto:success', 'expediente_medico.consultado:success']))
        === ['expediente_medico.abierto:success', 'expediente_medico.consultado:success']);

    // N-11..N-12 Errores de infraestructura: código estable, sin SQL ni datos clínicos.
    ['router' => $router] = make_app($adapter, storeDown: true);
    $down = call($router, 'GET', 'P-0001');
    check($t('N-11 almacén caído → 503 EXPEDIENTE_NO_DISPONIBLE con solo el código'), code($down) === '503 EXPEDIENTE_NO_DISPONIBLE' && array_keys($down['body']) === ['code']);
    ['router' => $router] = make_app($adapter, auditDown: true);
    $noAudit = call($router, 'GET', 'P-0001');
    check($t('N-12 bitácora caída → 503 AUDITORIA_NO_DISPONIBLE sin datos del expediente'), code($noAudit) === '503 AUDITORIA_NO_DISPONIBLE' && !str_contains($noAudit['raw'], 'EXP-'));

    // N-13 Rutas y métodos fuera del contrato.
    $delete = $router->dispatch(new Request('DELETE', '/patients/P-0001/medical-record', ['X-User-ID' => DRA_LOPEZ, 'X-Tenant-ID' => HOSPITAL]));
    $missing = $router->dispatch(new Request('GET', '/patients//medical-record'));
    check($t('N-13 DELETE → 405 (no se borra un expediente); ruta vacía → 404'), $delete['status'] === 405 && $missing['status'] === 404);
}

// N-14 DEF-EXP-10: la interfaz se sirve con Cache-Control: no-cache y sin salir de public/ui.
$ui = new \MicroHis\ExpedienteMedico\Presentation\StaticAsset(__DIR__ . '/../public/ui');
$js = $ui->render('app.js');
check('N-14 interfaz: app.js → 200 JS con no-cache; «/» → index.html; «..», .php y archivo inexistente → 404', $js['status'] === 200
    && $js['headers']['Cache-Control'] === 'no-cache' && str_starts_with($js['headers']['Content-Type'], 'text/javascript')
    && str_contains($ui->render('')['body'], 'app.js') && $ui->render('../index.php')['status'] === 404
    && $ui->render('../../app.php')['status'] === 404 && $ui->render('no-existe.js')['status'] === 404);
