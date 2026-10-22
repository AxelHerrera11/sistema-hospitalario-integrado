<?php

declare(strict_types=1);

use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Persistence\PdoAdmissionRepository;
use MicroHis\Admision\Presentation\AdmissionController;
use MicroHis\Admision\Presentation\AvailableBedsView;

// Front controller. Ejecutar: php -S 127.0.0.1:8081 -t micro-his/admision-camas/public
require __DIR__ . '/../bootstrap.php';
date_default_timezone_set(getenv('MICROHIS_TIMEZONE') ?: 'America/Guatemala'); // «hoy» y los plazos son del hospital, no UTC

$pdo = new PDO(getenv('MICROHIS_DSN') ?: 'sqlite:' . sys_get_temp_dir() . '/microhis-admision.sqlite', getenv('MICROHIS_DB_USER') ?: null, getenv('MICROHIS_DB_PASSWORD') ?: null);
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' && !$pdo->query("SELECT 1 FROM sqlite_master WHERE name = 'beds'")->fetchColumn()) {
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO local_patients VALUES ('P-DEMO'); INSERT INTO beds VALUES ('B-1', 'MED-101', 'adult', 'available'), ('B-2', 'PED-201', 'pediatric', 'available')"); // datos ficticios
}
$repository = new PdoAdmissionRepository($pdo);
$controller = new AdmissionController(new AdmitPatient($repository, fn () => date(DATE_ATOM), fn () => bin2hex(random_bytes(16))),
    new FindAvailableBeds($repository), new AvailableBedsView());
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? (array) json_decode((string) file_get_contents('php://input'), true) : $_GET;
[$status, $body] = $controller->handle($_SERVER['REQUEST_METHOD'], (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $input, $_SERVER['HTTP_X_USER_REF'] ?? null);
http_response_code($status);
header('Content-Type: ' . (is_string($body) ? 'text/html' : 'application/json') . '; charset=utf-8');
echo is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
