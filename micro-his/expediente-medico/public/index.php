<?php

declare(strict_types=1);

// Front controller del PROTOTIPO (Semanas 5 y 11). Solo para un entorno local aislado:
//   php -S 127.0.0.1:8096 -t micro-his/expediente-medico/public micro-his/expediente-medico/public/index.php
// Abre http://127.0.0.1:8096/ui/. Usa los adaptadores PDO sobre SQLite locales (datos ficticios de database/demo.sql)
// y la bitácora real de Gobernanza (PdoAuditLog). Variables opcionales:
//   EXPEDIENTE_DEMO_DIR=<carpeta>          dónde guardar las bases SQLite del prototipo (por defecto, la temporal del sistema)
//   EXPEDIENTE_PROPUESTA_APERTURA=0        desactiva la concesión PROPUESTA de apertura (se ve el 403 real)
//   EXPEDIENTE_DEMO_AUDIT=caida            simula la bitácora caída (error crítico AUDITORIA_NO_DISPONIBLE)
// No hay autenticación real: la identidad llega en X-User-ID (PROVISIONAL, ver README del módulo).

use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Persistence\HospitalRegistry;
use MicroHis\ExpedienteMedico\Presentation\JsonView;
use MicroHis\ExpedienteMedico\Presentation\Request;
use MicroHis\ExpedienteMedico\Presentation\StaticAsset;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Persistence\PdoAuditLog;

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/') {
    header('Location: /ui/', true, 302);
    return;
}

require __DIR__ . '/../app.php';

/** Envía la respuesta {status, headers, body} de la vista JSON o de StaticAsset. */
function expediente_emitir(array $response): void
{
    http_response_code($response['status']);
    foreach ($response['headers'] as $name => $value) {
        header("$name: $value");
    }
    echo $response['body'];
}

// DEF-EXP-10: la interfaz se sirve desde aquí (y no con `return false`) para enviar Cache-Control: no-cache.
if (str_starts_with($path, '/ui/')) {
    expediente_emitir((new StaticAsset(__DIR__ . '/ui'))->render(substr($path, 4)));
    return;
}

/** Abre (y la primera vez crea) una base SQLite del prototipo a partir de los scripts indicados. */
function expediente_demo_db(string $file, array $scripts): PDO
{
    $fresh = !is_file($file);
    $pdo = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    foreach ($fresh ? $scripts : [] as $script) {
        $pdo->exec((string) file_get_contents($script));
    }

    return $pdo;
}

$dir = getenv('EXPEDIENTE_DEMO_DIR') ?: sys_get_temp_dir();
$view = new JsonView();
try {
    $logger = getenv('EXPEDIENTE_DEMO_AUDIT') === 'caida'
        ? new class implements AuditLogger {
            public function append(AuditEvent $event): void
            {
                throw new AuditStorageFailure('audit_write_failed');
            }
        }
        : new PdoAuditLog(expediente_demo_db("$dir/expediente-demo-audit.sqlite", [__DIR__ . '/../../gobernanza-auditoria/database/audit_log.sqlite.sql']));
    $hospitals = new HospitalRegistry(['hospital-demo' => static fn (): HospitalStores => expediente_pdo_stores(
        expediente_demo_db("$dir/expediente-demo-hospital.sqlite", [__DIR__ . '/../database/schema.sql', __DIR__ . '/../database/demo.sql']),
    )]);
    $router = expediente_router(
        expediente_rbac(require __DIR__ . '/../database/demo-memberships.php', getenv('EXPEDIENTE_PROPUESTA_APERTURA') === '0' ? [] : expediente_propuesta_apertura()),
        $hospitals,
        expediente_audit($logger),
    );
    $response = $router->dispatch(new Request($_SERVER['REQUEST_METHOD'] ?? 'GET', $path, [
        'X-User-ID' => $_SERVER['HTTP_X_USER_ID'] ?? '',
        'X-Tenant-ID' => $_SERVER['HTTP_X_TENANT_ID'] ?? '',
        'X-Correlation-ID' => $_SERVER['HTTP_X_CORRELATION_ID'] ?? '',
    ]));
} catch (\Throwable) {
    // Fallo al preparar el prototipo (p. ej. carpeta sin permisos): sin detalles internos.
    $response = $view->render(503, ['code' => 'EXPEDIENTE_NO_DISPONIBLE']);
}

expediente_emitir($response);
