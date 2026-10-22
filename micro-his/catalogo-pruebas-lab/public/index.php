<?php

declare(strict_types=1);

use MicroHis\CatalogoLab\Application\FindActiveLabTests;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Persistence\PdoLabTestRepository;
use MicroHis\CatalogoLab\Presentation\LabTestController;
use MicroHis\CatalogoLab\Presentation\LabTestListView;

// Front controller. Ejecutar: php -S 127.0.0.1:8083 -t micro-his/catalogo-pruebas-lab/public
require __DIR__ . '/../bootstrap.php';
date_default_timezone_set(getenv('MICROHIS_TIMEZONE') ?: 'America/Guatemala'); // «hoy» y los plazos son del hospital, no UTC

$pdo = new PDO(getenv('MICROHIS_DSN') ?: 'sqlite:' . sys_get_temp_dir() . '/microhis-catalogo.sqlite', getenv('MICROHIS_DB_USER') ?: null, getenv('MICROHIS_DB_PASSWORD') ?: null);
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' && !$pdo->query("SELECT 1 FROM sqlite_master WHERE name = 'lab_tests'")->fetchColumn()) {
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
}
$repository = new PdoLabTestRepository($pdo);
$controller = new LabTestController(new RegisterLabTest($repository, fn () => date('Y-m-d'), fn () => bin2hex(random_bytes(16))),
    new FindActiveLabTests($repository), new LabTestListView());
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? (array) json_decode((string) file_get_contents('php://input'), true) : $_GET;
[$status, $body] = $controller->handle($_SERVER['REQUEST_METHOD'], (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $input, $_SERVER['HTTP_X_USER_REF'] ?? null);
http_response_code($status);
header('Content-Type: ' . (is_string($body) ? 'text/html' : 'application/json') . '; charset=utf-8');
echo is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
