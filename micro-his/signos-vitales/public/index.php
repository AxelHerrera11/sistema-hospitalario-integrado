<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\PdoCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\PdoVitalSignRepository;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
use MicroHis\SignosVitales\Presentation\JsonView;
use MicroHis\SignosVitales\Presentation\Request;

// Front controller: php -S localhost:8013 -t micro-his/signos-vitales/public
// Con SIGNOS_VITALES_DSN usa PDO; sin ella usa InMemory con datos ficticios (ep1: adulto abierto de dra.lopez), que se reinician en cada petición.
require __DIR__ . '/../app.php';

$dsn = getenv('SIGNOS_VITALES_DSN');
if ($dsn !== false && $dsn !== '') {
    $pdo = new PDO($dsn, getenv('SIGNOS_VITALES_DB_USER') ?: null, getenv('SIGNOS_VITALES_DB_PASSWORD') ?: null);
    $router = signos_vitales_router(new PdoCareEpisodeReader($pdo), new PdoVitalSignRepository($pdo), new QueuedAlertNotifier());
} else {
    $router = signos_vitales_router(new InMemoryCareEpisodeReader(['ep1' => ['dra.lopez' => new EpisodeContext(true, 45)]]), new InMemoryVitalSignRepository(), new QueuedAlertNotifier());
}

$raw = (string) file_get_contents('php://input');
$body = $raw === '' ? [] : json_decode($raw, true);
$response = is_array($body)
    ? $router->dispatch(new Request($_SERVER['REQUEST_METHOD'] ?? 'GET', (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), ['X-User-ID' => $_SERVER['HTTP_X_USER_ID'] ?? ''], $body))
    : (new JsonView())->render(422, ['code' => 'PAYLOAD_INVALID']);

http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header("$name: $value");
}
echo $response['body'];
