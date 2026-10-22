<?php

declare(strict_types=1);

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Persistence\PdoAllergyRepository;
use MicroHis\Alergias\Presentation\AllergyBannerView;
use MicroHis\Alergias\Presentation\AllergyController;

// Front controller. Ejecutar: php -S 127.0.0.1:8082 -t micro-his/alergias/public
require __DIR__ . '/../bootstrap.php';
date_default_timezone_set(getenv('MICROHIS_TIMEZONE') ?: 'America/Guatemala'); // «hoy» y los plazos son del hospital, no UTC

$pdo = new PDO(getenv('MICROHIS_DSN') ?: 'sqlite:' . sys_get_temp_dir() . '/microhis-alergias.sqlite', getenv('MICROHIS_DB_USER') ?: null, getenv('MICROHIS_DB_PASSWORD') ?: null);
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' && !$pdo->query("SELECT 1 FROM sqlite_master WHERE name = 'allergies'")->fetchColumn()) {
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO local_patients VALUES ('P-DEMO')"); // paciente ficticio para la demo
}
$repository = new PdoAllergyRepository($pdo);
$controller = new AllergyController(new RegisterAllergy($repository, fn () => bin2hex(random_bytes(16))), new GetAllergyAlert($repository), new AllergyBannerView());
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? (array) json_decode((string) file_get_contents('php://input'), true) : $_GET;
[$status, $body] = $controller->handle($_SERVER['REQUEST_METHOD'], (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $input, $_SERVER['HTTP_X_USER_REF'] ?? null);
http_response_code($status);
header('Content-Type: ' . (is_string($body) ? 'text/html' : 'application/json') . '; charset=utf-8');
echo is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
