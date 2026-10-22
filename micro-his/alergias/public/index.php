<?php

declare(strict_types=1);

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Persistence\PdoAllergyRepository;
use MicroHis\Alergias\Presentation\ApiKernel;
use MicroHis\Alergias\Presentation\AllergyBannerView;
use MicroHis\Alergias\Presentation\AllergyController;

// Front controller del servidor API v1. Ejecutar: php -S 127.0.0.1:8082 -t micro-his/alergias/public
require __DIR__ . '/../bootstrap.php';
date_default_timezone_set(getenv('MICROHIS_TIMEZONE') ?: 'America/Guatemala'); // «hoy» y los plazos son del hospital, no UTC

$pdo = new PDO(getenv('MICROHIS_DSN') ?: 'sqlite:' . sys_get_temp_dir() . '/microhis-alergias.sqlite', getenv('MICROHIS_DB_USER') ?: null, getenv('MICROHIS_DB_PASSWORD') ?: null);
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' && !$pdo->query("SELECT 1 FROM sqlite_master WHERE name = 'allergies'")->fetchColumn()) {
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO local_patients VALUES ('P-DEMO')"); // paciente ficticio para la demo
}
$repository = new PdoAllergyRepository($pdo);
$controller = new AllergyController(new RegisterAllergy($repository, fn () => bin2hex(random_bytes(16))), new GetAllergyAlert($repository), new AllergyBannerView());
$ready = static function () use ($pdo): bool {
    try {
        return (int) $pdo->query('SELECT 1')->fetchColumn() === 1;
    } catch (PDOException) {
        return false;
    }
};
$kernel = new ApiKernel($controller, getenv('MICROHIS_TENANT') ?: 'HOSP-DEMO', $ready);
[$status, $headers, $body] = $kernel->handle($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], array_change_key_case(getallheaders()), (string) file_get_contents('php://input'));
http_response_code($status);
array_walk($headers, static fn (string $value, string $name) => header("{$name}: {$value}"));
echo $body;
// Log de acceso sin datos clínicos: solo método, ruta, estado y correlación.
error_log(json_encode(['method' => $_SERVER['REQUEST_METHOD'], 'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 'status' => $status, 'request_id' => $headers['X-Request-Id']]));
