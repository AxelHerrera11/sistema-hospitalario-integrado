<?php

declare(strict_types=1);

// Punto de entrada de consola del micro-HIS (raíz de composición). Uso:
//   TRASLADOS_PG_DSN="pgsql:host=...;port=...;dbname=..." TRASLADOS_PG_PASSWORD=... TRASLADOS_ACTOR_REF=<local_user_ref_id> \
//   php micro-his/traslados-altas/bin/traslados.php trasladar <estancia_id> <cama_destino_id> "<motivo>"
//   php micro-his/traslados-altas/bin/traslados.php alta <estancia_id>
// Imprime JSON y sale con 0 (éxito), 1 (rechazo), 2 (fallo), 3 (incierto) o 64 (solicitud inválida).

use MicroHis\TrasladosAltas\Persistence\AutorizadorReferenciaActiva;
use MicroHis\TrasladosAltas\Persistence\PdoRegistroInternacion;
use MicroHis\TrasladosAltas\Presentation\ConsolaTraslados;
use MicroHis\TrasladosAltas\Presentation\VistaResultado;

require __DIR__ . '/../bootstrap.php';

$conectar = static fn (): PDO => new PDO((string) getenv('TRASLADOS_PG_DSN'), getenv('TRASLADOS_PG_USER') ?: 'postgres',
    (string) getenv('TRASLADOS_PG_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
try {
    // Dos conexiones: la autorización se consulta antes y fuera de la transacción de la operación (RF-TA-03).
    $consola = new ConsolaTraslados(new PdoRegistroInternacion($conectar()), new AutorizadorReferenciaActiva($conectar()),
        (string) getenv('TRASLADOS_ACTOR_REF'));
    $respuesta = $consola->atender(array_slice($argv, 1));
} catch (PDOException) {
    // Sin conexión no se abrió ninguna transacción: se sabe que no hubo cambios. No se muestran detalles internos.
    $respuesta = ['codigo' => VistaResultado::FALLO, 'cuerpo' => ['resultado' => 'fallo', 'codigo' => 'HOSPITAL_NO_DISPONIBLE',
        'mensaje' => 'No se pudo conectar con HOSPITAL; no se guardó ningún cambio.']];
}
echo json_encode($respuesta['cuerpo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($respuesta['codigo']);
