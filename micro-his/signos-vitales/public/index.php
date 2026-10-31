<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Domain\Uuid;
use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\InMemoryPermissionChecker;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\PdoCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\PdoVitalSignRepository;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
use MicroHis\SignosVitales\Presentation\Request;

// Front controller del API v1: php -S localhost:8013 -t micro-his/signos-vitales/public
// Con SIGNOS_VITALES_DSN usa PDO; sin ella usa InMemory con datos ficticios (ep1: adulto abierto de dra.lopez), que se reinician en cada petición.
// RBAC es un doble en memoria hasta integrar ASII-02.
require __DIR__ . '/../app.php';

$traceId = Uuid::v4();
$permissions = new InMemoryPermissionChecker([
    'dra.lopez' => ['signos_vitales.registrar', 'signos_vitales.ver'],
    'enf.ruiz' => ['signos_vitales.ver'],
]);
$dsn = getenv('SIGNOS_VITALES_DSN');
if ($dsn !== false && $dsn !== '') {
    $pdo = new PDO($dsn, getenv('SIGNOS_VITALES_DB_USER') ?: null, getenv('SIGNOS_VITALES_DB_PASSWORD') ?: null);
    $parts = [new PdoCareEpisodeReader($pdo), new PdoVitalSignRepository($pdo), new QueuedAlertNotifier()];
} else {
    $parts = [new InMemoryCareEpisodeReader(['ep1' => ['dra.lopez' => new EpisodeContext(true, 45)]]), new InMemoryVitalSignRepository(), new QueuedAlertNotifier()];
}
// Bitácora de acceso en stderr, una línea JSON por petición y sin el cuerpo (observabilidad sin datos clínicos).
$log = static fn (array $entry) => error_log(json_encode(['event' => 'api_access'] + $entry, JSON_UNESCAPED_SLASHES));
// El fallo del canal de notificación se registra con el id de la medición y la causa, nunca con valores clínicos.
$notificationFailure = static fn (string $id, Throwable $e) => error_log(json_encode(['event' => 'notification_failed', 'vital_sign_id' => $id, 'cause' => $e::class], JSON_UNESCAPED_SLASHES));
$api = signos_vitales_api(...$parts, permissions: $permissions, traceIds: static fn (): string => $traceId, log: $log, onNotificationFailure: $notificationFailure);

$raw = (string) file_get_contents('php://input');
$body = $raw === '' ? [] : json_decode($raw, true);
$response = $api->handle(new Request($_SERVER['REQUEST_METHOD'] ?? 'GET', (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), ['X-User-ID' => $_SERVER['HTTP_X_USER_ID'] ?? ''], is_array($body) ? $body : []), is_array($body));
http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header("$name: $value");
}
echo $response['body'];
