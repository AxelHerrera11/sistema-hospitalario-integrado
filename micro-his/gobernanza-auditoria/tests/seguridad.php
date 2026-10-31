<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\Gobernanza\Persistence\PdoAuditLog;

require __DIR__ . '/harness.php';

/**
 * Seguridad (Semana 17): datos personales en la bitácora (H-GOB-17-01) y orden de las comprobaciones de acceso.
 * Cada prueba corresponde a una amenaza de docs/gobernanza-auditoria/semana-17/matriz-amenazas.md.
 */
$personal = ['email' => 'ana@hospital.test', 'dpi' => '2456789010101', 'telefono' => '5555-1234', 'direccion' => '5a avenida zona 1',
    'fecha_nacimiento' => '1980-05-14', 'usuario' => 'ana@hospital.test', 'canal' => 'web', 'circuito' => 'norte'];

foreach (['InMemory' => static fn () => new InMemoryAuditLog(), 'PDO' => static fn () => pdoStore()] as $name => $factory) {
    section("Datos personales en el contexto ({$name})");
    $app = compose($factory());
    $app['recorder']->record(ADMIN, H1, 'usuarios', 'usuario.creado', AuditOutcome::Success, AuditCriticality::Security, 'central_user', 'u-1', 'trace-pii01', null, $personal);
    $event = $app['http']->search(...as_user(ADMIN, H1), ...[['correlacion' => 'trace-pii01']])['body']['data'][0];
    check("TC-S-01/{$name}", 'email, DPI, teléfono, dirección y fecha de nacimiento no se guardan y quedan en redacted_keys',
        $event['context'] === ['canal' => 'web', 'circuito' => 'norte']
        && array_diff(['email', 'dpi', 'telefono', 'direccion', 'fecha_nacimiento'], $event['redacted_keys']) === []);
    check("TC-S-02/{$name}", 'un correo bajo una clave neutra («usuario») también se elimina', in_array('usuario', $event['redacted_keys'], true));
    check("TC-S-03/{$name}", 'ningún valor personal aparece en la respuesta', preg_match('/hospital\.test|2456789010101|5555-1234|zona 1|1980-05-14/', (string) json_encode($event)) === 0);
}

section('Filas de otro productor con datos personales');
$pdo = sqlitePdo();
$store = new PdoAuditLog($pdo);
$pdo->prepare('INSERT INTO audit_log (event_id, occurred_at, event, actor_id, hospital_id, detail, trace_id, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute(['6a1d9b2e-4c3f-4e5a-8b7c-1d2e3f4a5b6c', '2026-10-15T20:00:00.000000Z', 'usuarios.usuario.creado', ADMIN, H1,
        json_encode(['email' => 'ana@hospital.test', 'telefono' => '5555-1234', 'role_code' => 'medico']), 'trace-pii02', 'success']);
$foreign = $store->search(H1, new AuditFilter(correlationId: 'trace-pii02'))->events[0];
check('TC-S-04', 'al leer filas ajenas también se quitan email y teléfono (role_code se conserva)', $foreign->context === ['role_code' => 'medico']
    && array_diff(['email', 'telefono'], $foreign->redactedKeys) === []);

section('Orden de las comprobaciones');
$app = compose(new InMemoryAuditLog());
$medico = $app['http']->search(...as_user(MED, H1), ...[['por_pagina' => '0', 'modulo' => "rbac' OR 1=1--"]]);
check('TC-S-05', 'sin permiso, un filtro inválido devuelve 403 (no 422): no se revela cómo valida la API', $medico['status'] === 403 && $medico['body']['code'] === 'RBAC_DENIED');
$admin = $app['http']->search(...as_user(ADMIN, H1), ...[['modulo' => "rbac' OR 1=1--"]]);
check('TC-S-06', 'con permiso, inyección en «modulo» → 422 sin eco del valor ni texto SQL', $admin['status'] === 422
    && preg_match('/OR 1=1|SQL|SELECT|PDO/i', (string) json_encode($admin['body'])) === 0);
check('TC-S-07', 'toda respuesta (200, 403, 422) lleva Cache-Control: no-store, private', $medico['headers']['Cache-Control'] === 'no-store, private'
    && $admin['headers']['Cache-Control'] === 'no-store, private');

summary('seguridad');
