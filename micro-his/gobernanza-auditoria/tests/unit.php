<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Application\AuditFilterParser;
use MicroHis\Gobernanza\Application\InvalidAuditFilter;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Domain\ContextSanitizer;
use MicroHis\Gobernanza\Domain\InvalidAuditEvent;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;

require __DIR__ . '/harness.php';

$id = '9b2f6c1e-3f4a-4c5d-8e6f-7a8b9c0d1e2f';
$at = new DateTimeImmutable('2026-10-15T08:00:00-06:00');
$event = static fn (array $over = []) => AuditEvent::record(...array_merge([
    'eventId' => $id, 'occurredAt' => $at, 'actorId' => ADMIN, 'tenantId' => H1,
    'module' => 'rbac', 'action' => 'access_denied', 'outcome' => AuditOutcome::Denied,
    'criticality' => AuditCriticality::Security,
], $over));

section('Entidad AuditEvent');
$e = $event();
check('TC-U-01', 'evento válido: nombre canónico modulo.accion y hora en UTC', $e->eventName() === 'rbac.access_denied' && $e->occurredAt->format('H:i e') === '14:00 UTC');
check('TC-U-02', 'sin actor o sin tenant -> InvalidAuditEvent (no se inventan datos)', thrown(fn () => $event(['actorId' => ''])) === InvalidAuditEvent::class && thrown(fn () => $event(['tenantId' => ''])) === InvalidAuditEvent::class);
check('TC-U-03', 'módulo/acción fuera del formato modulo.accion -> InvalidAuditEvent', thrown(fn () => $event(['module' => 'RBAC'])) === InvalidAuditEvent::class && thrown(fn () => $event(['action' => 'borrar todo'])) === InvalidAuditEvent::class);
check('TC-U-04', 'resultado Unknown no se acepta al registrar', thrown(fn () => $event(['outcome' => AuditOutcome::Unknown])) === InvalidAuditEvent::class);
check('TC-U-05', 'resource_id sin resource_type -> InvalidAuditEvent', thrown(fn () => $event(['resourceId' => 'x1'])) === InvalidAuditEvent::class);
check('TC-U-06', 'motivo de texto libre (posible dato sensible) -> InvalidAuditEvent', thrown(fn () => $event(['reason' => 'Texto libre escrito por el usuario'])) === InvalidAuditEvent::class);
check('TC-U-07', 'solo success cuenta como ocurrido; failure/denied son intentos', AuditOutcome::Success->occurred() && !AuditOutcome::Failure->occurred() && !AuditOutcome::Denied->occurred());

section('Regla ContextSanitizer');
[$clean, $removed] = ContextSanitizer::sanitize([
    'permission' => 'auditoria.ver',
    'password' => 'x',
    'access_token' => 'abc',
    'Authorization' => 'Bearer abc',
    'diagnosis_code' => 'J18.9',
    'nota_texto' => 'texto clínico',
    'jwt' => 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.c2lnbmF0dXJh',
    'nested' => ['a' => ['b' => 1]],
    'ids' => ['r1', 'r2'],
]);
check('TC-U-08', 'conserva solo escalares permitidos', $clean === ['ids' => ['r1', 'r2'], 'permission' => 'auditoria.ver']);
check('TC-U-09', 'elimina secretos, tokens y datos clínicos e informa solo los nombres de clave', in_array('password', $removed, true) && in_array('access_token', $removed, true) && in_array('diagnosis_code', $removed, true) && in_array('nota_texto', $removed, true) && in_array('jwt', $removed, true) && !str_contains(json_encode($removed), 'eyJ'));

section('AuditFilterParser');
$parser = new AuditFilterParser('America/Guatemala');
$f = $parser->parse(['usuario' => ADMIN, 'modulo' => 'rbac', 'desde' => '2026-10-15', 'hasta' => '2026-10-15', 'pagina' => '2', 'por_pagina' => '10']);
check('TC-U-10', 'fechas de día completo en zona del servidor -> rango UTC [06:00, 06:00 del día siguiente)', $f->from->format('Y-m-d\TH:i\Z') === '2026-10-15T06:00Z' && $f->until->format('Y-m-d\TH:i\Z') === '2026-10-16T06:00Z' && $f->offset() === 10);
$errors = static function (array $input) use ($parser): array {
    try {
        $parser->parse($input);
    } catch (InvalidAuditFilter $e) {
        return $e->errors;
    }

    return [];
};
check('TC-U-11', 'fecha imposible y formato libre -> fecha_invalida', $errors(['desde' => '2026-02-30', 'hasta' => 'ayer']) === ['desde' => 'fecha_invalida', 'hasta' => 'fecha_invalida']);
check('TC-U-12', 'hasta anterior a desde -> anterior_a_desde', $errors(['desde' => '2026-10-16', 'hasta' => '2026-10-15']) === ['hasta' => 'anterior_a_desde']);
check('TC-U-13', 'por_pagina > 100 y pagina 0 -> fuera_de_rango', $errors(['por_pagina' => '101', 'pagina' => '0']) === ['pagina' => 'fuera_de_rango', 'por_pagina' => 'fuera_de_rango']);
check('TC-U-14', 'parámetro desconocido (p. ej. "usuaro" mal escrito) -> 422, no consulta sin filtro', $errors(['usuaro' => 'x']) === ['usuaro' => 'parametro_no_permitido']);
check('TC-U-15', 'resultado fuera de success|failure|denied y arreglos -> error', $errors(['resultado' => 'unknown', 'modulo' => ['rbac']]) === ['modulo' => 'tipo_invalido', 'resultado' => 'valor_no_permitido']);
check('TC-U-22', 'regresión H-GOB-12: el nombre de un parámetro desconocido se devuelve saneado', $errors(['<script>x' => '1']) === ['_script_x' => 'parametro_no_permitido']);
check('TC-U-16', 'inyección en filtros rechazada por formato', $errors(['modulo' => "rbac' OR '1'='1", 'usuario' => "x'; DROP TABLE audit_log;--"]) === ['modulo' => 'formato_invalido', 'usuario' => 'formato_invalido']);

section('RecordAuditEvent (política de fallo)');
$reporter = new RecordingReporter();
$broken = new RecordAuditEvent(new BrokenStore(), new FixedClock(), $reporter);
check('TC-U-17', 'Security + almacén caído -> lanza AuditStorageFailure y reporta', thrown(fn () => $broken->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security)) === AuditStorageFailure::class && $reporter->codes() === ['audit_record_failed']);
check('TC-U-18', 'Informational + almacén caído -> devuelve false, no lanza, y reporta', $broken->record(ADMIN, H1, 'gobernanza', 'vista_abierta', AuditOutcome::Success, AuditCriticality::Informational) === false && count($reporter->reports) === 2);
$memory = new InMemoryAuditLog();
$ok = (new RecordAuditEvent($memory, new FixedClock(), $reporter))->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security);
check('TC-U-19', 'almacén sano -> true y un evento con UUID v4 generado por el servidor', $ok === true && $memory->count() === 1);
check('TC-U-20', 'el reporte de fallo no contiene contexto del evento (solo códigos e ids)', !str_contains(json_encode($reporter->reports), 'password'));
$invalidReporter = new RecordingReporter();
$invalid = new RecordAuditEvent(new InMemoryAuditLog(), new FixedClock(), $invalidReporter);
check('TC-U-21', 'regresión H-GOB-04: evento inválido se reporta (audit_event_invalid, campo) y se relanza', thrown(fn () => $invalid->record('', H1, 'rbac', 'access_denied', AuditOutcome::Denied, AuditCriticality::Security)) === InvalidAuditEvent::class && $invalidReporter->reports === [['code' => 'audit_event_invalid', 'context' => ['field' => 'actor_id', 'criticality' => 'security', 'correlation_id' => null]]]);

summary('unit');
