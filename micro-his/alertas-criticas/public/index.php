<?php

declare(strict_types=1);

use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\GetInbox;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Persistence\PdoAlertRepository;
use MicroHis\Alertas\Presentation\AlertInboxView;
use MicroHis\Alertas\Presentation\CriticalAlertController;

// Front controller. Ejecutar: php -S 127.0.0.1:8084 -t micro-his/alertas-criticas/public
require __DIR__ . '/../bootstrap.php';

$pdo = new PDO(getenv('MICROHIS_DSN') ?: 'sqlite:' . sys_get_temp_dir() . '/microhis-alertas.sqlite', getenv('MICROHIS_DB_USER') ?: null, getenv('MICROHIS_DB_PASSWORD') ?: null);
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' && !$pdo->query("SELECT 1 FROM sqlite_master WHERE name = 'critical_alerts'")->fetchColumn()) {
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO lab_tests VALUES ('GLU-v1', 40, 400); INSERT INTO care_assignments VALUES ('EP-DEMO', 'P-DEMO', 'dra.lopez', 'active')"); // datos ficticios
}
$repository = new PdoAlertRepository($pdo);
$controller = new CriticalAlertController(new HandleResultValidated($repository, fn () => new DateTimeImmutable(), fn () => bin2hex(random_bytes(16))),
    new AcknowledgeAlert($repository), new GetInbox($repository), new AlertInboxView());
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? (array) json_decode((string) file_get_contents('php://input'), true) : $_GET;
[$status, $body] = $controller->handle($_SERVER['REQUEST_METHOD'], (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $input, $_SERVER['HTTP_X_USER_REF'] ?? null);
http_response_code($status);
header('Content-Type: ' . (is_string($body) ? 'text/html' : 'application/json') . '; charset=utf-8');
echo is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
