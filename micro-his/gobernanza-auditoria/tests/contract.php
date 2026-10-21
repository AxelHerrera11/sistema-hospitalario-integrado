<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;

require __DIR__ . '/harness.php';

/**
 * Prueba de sustitución: los MISMOS escenarios y casos de uso se ejecutan
 * contra InMemoryAuditLog y PdoAuditLog (SQLite con el esquema de audit_log).
 * Si ambos adaptadores cumplen, los consumidores no cambian al sustituirlos.
 */
$adapters = ['InMemory' => static fn () => new InMemoryAuditLog(), 'PDO' => static fn () => pdoStore()];

foreach ($adapters as $name => $factory) {
    section("Contrato AuditLogger/AuditEventReader — {$name}");
    $store = $factory();
    $clock = new FixedClock(new DateTimeImmutable('2026-10-15T14:00:00Z'));
    $rec = new RecordAuditEvent($store, $clock, new RecordingReporter());
    $rec->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security, 'membership', 'm-1', 'trace-aaa111');
    $rec->record(MED, H1, 'rbac', 'access_denied', AuditOutcome::Denied, AuditCriticality::Security, 'permission', 'auditoria.ver', 'trace-bbb222', 'permiso_faltante');
    $rec->record(MED, H1, 'notas_soap', 'nota.firmada', AuditOutcome::Success, AuditCriticality::Security, 'clinical_note', 'n-1', null, null, ['version_no' => 1, 'note_text' => 'no debe guardarse']);
    $clock->set('2026-10-16T15:00:00Z');
    $rec->record(MED, H1, 'notasxsoap', 'nota.firmada', AuditOutcome::Failure, AuditCriticality::Informational);
    $rec->record(ADMIN, H2, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security, 'membership', 'm-9');

    $all = $store->search(H1, new AuditFilter());
    check("TC-C-01/{$name}", 'aislamiento: solo devuelve eventos del tenant pedido por el servidor', $all->total === 4 && array_unique(array_map(fn ($e) => $e->tenantId, $all->events)) === [H1]);
    check("TC-C-02/{$name}", 'orden: más reciente primero', $all->events[0]->module === 'notasxsoap' && $all->events[3]->action === 'role_assigned');
    check("TC-C-03/{$name}", 'filtro usuario', $store->search(H1, new AuditFilter(actorId: ADMIN))->total === 1);
    check("TC-C-04/{$name}", 'filtro módulo literal: "notas_soap" no coincide con "notasxsoap" (comodín _ escapado)', $store->search(H1, new AuditFilter(module: 'notas_soap'))->total === 1);
    check("TC-C-05/{$name}", 'filtro acción con punto interno ("nota.firmada")', $store->search(H1, new AuditFilter(action: 'nota.firmada'))->total === 2);
    check("TC-C-06/{$name}", 'filtro resultado denied (intentos denegados)', $store->search(H1, new AuditFilter(outcome: AuditOutcome::Denied))->events[0]->reason === 'permiso_faltante');
    check("TC-C-07/{$name}", 'filtro correlación', $store->search(H1, new AuditFilter(correlationId: 'trace-aaa111'))->events[0]->resourceId === 'm-1');
    $range = new AuditFilter(from: new DateTimeImmutable('2026-10-15T14:00:01Z'), until: new DateTimeImmutable('2026-10-15T14:00:02Z'));
    check("TC-C-08/{$name}", 'rango [desde, hasta): incluye el límite inferior y excluye el superior', $store->search(H1, $range)->total === 1);
    $page2 = $store->search(H1, new AuditFilter(page: 2, perPage: 3));
    check("TC-C-09/{$name}", 'paginación: página 2 de 3 por página trae el resto y el total real', count($page2->events) === 1 && $page2->total === 4 && $page2->totalPages() === 2);
    $signed = $store->search(H1, new AuditFilter(module: 'notas_soap'))->events[0];
    check("TC-C-10/{$name}", 'contexto clínico no se persiste y queda listado en redacted_keys', $signed->context === ['version_no' => 1] && $signed->redactedKeys === ['note_text']);
    check("TC-C-11/{$name}", 'filtro combinado (usuario + módulo + resultado) sin coincidencias -> página vacía', $store->search(H1, new AuditFilter(actorId: ADMIN, module: 'notas_soap', outcome: AuditOutcome::Failure))->total === 0);
}

summary('contract');
