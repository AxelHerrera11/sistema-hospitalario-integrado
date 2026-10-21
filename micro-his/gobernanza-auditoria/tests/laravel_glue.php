<?php

declare(strict_types=1);

use App\GobernanzaAuditoria\AuditEventController;
use App\GobernanzaAuditoria\GobernanzaAuditoriaServiceProvider;
use Illuminate\Http\Request;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;

/**
 * Capa Laravel (app/GobernanzaAuditoria) sin base de datos: comprueba que el
 * controlador toma la identidad del guard y del atributo "tenant", no de la
 * petición. No cubre el ruteo ni los middleware reales (requieren PostgreSQL,
 * RbacServiceProvider y los modelos Tenant/User; ver Semana 15).
 */
$autoload = __DIR__ . '/../../../vendor/autoload.php';
if (!is_file($autoload)) {
    echo 'OMITIDA: falta vendor/ (ejecute composer install en la raíz).' . PHP_EOL;
    exit(0);
}
require $autoload;
require __DIR__ . '/harness.php';

$tenant = new class {
    public function getKey(): string
    {
        return H1;
    }
};
$user = new class {
    public function getAuthIdentifier(): string
    {
        return ADMIN;
    }
};
$request = static function (array $query, array $headers, ?object $who) use ($tenant): Request {
    $r = Request::create('/api/v1/audit-events', 'GET', $query);
    foreach ($headers as $k => $v) {
        $r->headers->set($k, $v);
    }
    $r->attributes->set('tenant', $tenant);
    $r->setUserResolver(static fn () => $who);

    return $r;
};

$store = new InMemoryAuditLog();
$app = compose($store);
$app['recorder']->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security);
$controller = new AuditEventController($app['http']);

section('AuditEventController (Laravel)');
$ok = $controller->index($request(['modulo' => 'rbac'], ['X-Tenant-ID' => H1], $user));
$body = json_decode((string) $ok->getContent(), true);
check('TC-L-01', 'GET con identidad del guard y tenant del middleware -> 200 JSON y Cache-Control no-store', $ok->getStatusCode() === 200 && $body['meta']['total'] === 1 && str_contains((string) $ok->headers->get('Cache-Control'), 'no-store'));
check('TC-L-02', 'sin usuario en el guard -> 401 aunque el cliente envíe X-User-ID', $controller->index($request([], ['X-Tenant-ID' => H1, 'X-User-ID' => ADMIN], null))->getStatusCode() === 401);
check('TC-L-03', 'X-Tenant-ID distinto al tenant del middleware -> 403', $controller->index($request([], ['X-Tenant-ID' => H2], $user))->getStatusCode() === 403);
check('TC-L-04', 'el proveedor carga y declara register/boot (no se registra en bootstrap/providers.php)', class_exists(GobernanzaAuditoriaServiceProvider::class) && method_exists(GobernanzaAuditoriaServiceProvider::class, 'boot'));

section('GobernanzaAuditoriaServiceProvider::register (contenedor Laravel real, sin base de datos)');
$laravel = new \Illuminate\Foundation\Application(realpath(__DIR__ . '/../../..'));
$laravel->instance('config', new \Illuminate\Config\Repository(['app' => ['timezone' => 'America/Guatemala']]));
(new GobernanzaAuditoriaServiceProvider($laravel))->register();
// Sustituciones de prueba: SQLite en lugar de CENTRAL y una puerta fija en lugar de RBAC.
$laravel->instance(\MicroHis\Gobernanza\Persistence\PdoAuditLog::class, pdoStore());
$laravel->instance(\MicroHis\Gobernanza\Application\AccessGate::class, new StaticGate([ADMIN . '@' . H1 => ['auditoria.ver']]));
$fromContainer = new AuditEventController($laravel->make(\MicroHis\Gobernanza\Presentation\AuditHttpHandler::class));
$laravel->make(\MicroHis\Gobernanza\Application\RecordAuditEvent::class)->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security);
$resolved = json_decode((string) $fromContainer->index($request(['modulo' => 'rbac'], ['X-Tenant-ID' => H1], $user))->getContent(), true);
check('TC-L-05', 'el cableado del proveedor (GobernanzaModule) resuelve RecordAuditEvent y AuditHttpHandler sobre PdoAuditLog', ($resolved['meta']['total'] ?? null) === 1);

summary('laravel_glue');
