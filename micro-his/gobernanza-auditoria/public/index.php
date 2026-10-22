<?php

declare(strict_types=1);

/**
 * SERVIDOR DE DEMOSTRACIÓN (Semanas 3 y 11). No es la integración real.
 *
 *   php -S localhost:8091 -t micro-his/gobernanza-auditoria/public
 *   -> http://localhost:8091/prototipo/
 *
 * Diferencias con producción, todas deliberadas y visibles:
 * - La identidad sale de la cabecera X-Demo-Session (admin | medico | admin_h2)
 *   en lugar de JWT + TenantMiddleware. En Laravel la aporta el servidor.
 * - Los permisos los decide StaticGate con "auditoria.ver" para admin.
 * - Los datos son ficticios y se regeneran en cada petición (InMemoryAuditLog).
 * - demo_falla=almacen|rbac y demo_latencia=<ms> simulan errores y carga.
 */
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path !== '/api/v1/audit-events') {
    if ($path === '/' || $path === '/index.php') {
        header('Location: /prototipo/');

        return true;
    }

    return false; // archivos estáticos de public/ (el prototipo)
}

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../tests/fixtures.php';

$query = $_GET;
$failure = is_string($query['demo_falla'] ?? null) ? $query['demo_falla'] : '';
$latency = min(3000, max(0, (int) ($query['demo_latencia'] ?? 0)));
unset($query['demo_falla'], $query['demo_latencia']);
$latency > 0 && usleep($latency * 1000);

$sessions = ['admin' => [ADMIN, H1], 'medico' => [MED, H1], 'admin_h2' => [ADMIN, H2]];
[$user, $tenant] = $sessions[$_SERVER['HTTP_X_DEMO_SESSION'] ?? ''] ?? [null, null];

$store = new InMemoryAuditLog();
$gate = new StaticGate([ADMIN . '@' . H1 => ['auditoria.ver']], $failure === 'rbac' ? 'resolucion_no_disponible' : null);
$app = compose($failure === 'almacen' ? new SplitStore(new BrokenStore(), $store) : $store, $gate, $store);
seed($app['recorder'], $app['clock']);

$result = $app['http']->search(
    ['user_id' => $user, 'tenant_id' => $tenant],
    ['X-Tenant-ID' => $_SERVER['HTTP_X_TENANT_ID'] ?? $tenant, 'X-Request-ID' => $_SERVER['HTTP_X_REQUEST_ID'] ?? null],
    $query,
);
http_response_code($result['status']);
header('Content-Type: application/json; charset=utf-8');
foreach ($result['headers'] as $name => $value) {
    header("{$name}: {$value}");
}
echo json_encode($result['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

/** 64 eventos ficticios deterministas entre el 12 y el 15 de octubre de 2026. */
function seed(\MicroHis\Gobernanza\Application\RecordAuditEvent $rec, FixedClock $clock): void
{
    $catalog = [
        ['rbac', 'role_assigned', AuditOutcome::Success, 'membership'],
        ['rbac', 'access_denied', AuditOutcome::Denied, 'permission'],
        ['usuarios', 'usuario.desactivado', AuditOutcome::Success, 'central_user'],
        ['notas_soap', 'nota.firmada', AuditOutcome::Success, 'clinical_note'],
        ['notas_soap', 'nota.firmada', AuditOutcome::Failure, 'clinical_note'],
        ['prescripciones', 'prescripcion.creada', AuditOutcome::Success, 'prescription'],
        ['prescripciones', 'prescripcion.bloqueada', AuditOutcome::Denied, 'prescription'],
        ['sesiones', 'sesion.revocada', AuditOutcome::Success, 'session'],
    ];
    for ($i = 0; $i < 64; $i++) {
        [$module, $action, $outcome, $type] = $catalog[$i % count($catalog)];
        $clock->set((new DateTimeImmutable('2026-10-12T14:00:00Z'))->modify('+' . ($i * 83) . ' minutes')->format(DATE_ATOM));
        $rec->record(
            $i % 3 === 0 ? ADMIN : MED,
            $i % 5 === 4 ? H2 : H1,
            $module,
            $action,
            $outcome,
            $module === 'prescripciones' ? AuditCriticality::Informational : AuditCriticality::Security,
            $type,
            sprintf('demo-%03d', $i),
            sprintf('trace-demo%04d', intdiv($i, 2)),
            $outcome === AuditOutcome::Success ? null : ($outcome === AuditOutcome::Denied ? 'permiso_faltante' : 'validacion_fallida'),
            ['canal' => $i % 2 ? 'web' : 'api', 'version_no' => $module === 'notas_soap' ? 1 + $i % 3 : null],
        );
    }
    $clock->set('2026-10-20T15:00:00Z');
}
