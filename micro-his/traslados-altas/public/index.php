<?php

declare(strict_types=1);

// Front controller del PROTOTIPO HTTP (Semana 5). Solo para un entorno local aislado:
//   TRASLADOS_PG_DSN=... TRASLADOS_PG_PASSWORD=... php -S 127.0.0.1:8790 micro-his/traslados-altas/public/index.php
// No tiene autenticación real: el actor llega en X-Actor-Ref y solo se comprueba que esté activo en local_user_refs.

use MicroHis\TrasladosAltas\Persistence\AutorizadorReferenciaActiva;
use MicroHis\TrasladosAltas\Persistence\PdoRegistroInternacion;
use MicroHis\TrasladosAltas\Presentation\HttpTraslados;

require __DIR__ . '/../bootstrap.php';

$conectar = static fn (): PDO => new PDO((string) getenv('TRASLADOS_PG_DSN'), getenv('TRASLADOS_PG_USER') ?: 'postgres',
    (string) getenv('TRASLADOS_PG_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$api = new HttpTraslados(static fn (): array => [new PdoRegistroInternacion($conectar()), new AutorizadorReferenciaActiva($conectar())]);
try {
    $respuesta = $api->atender($_SERVER['REQUEST_METHOD'] ?? 'GET', (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
        $_SERVER['HTTP_X_ACTOR_REF'] ?? null, (string) file_get_contents('php://input'));
} catch (PDOException) {
    // Sin conexión no se abrió ninguna transacción: no hubo cambios. No se exponen detalles internos.
    $respuesta = ['estado' => 503, 'cuerpo' => ['resultado' => 'error', 'codigo' => 'HOSPITAL_NO_DISPONIBLE']];
}
http_response_code($respuesta['estado']);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode($respuesta['cuerpo'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
