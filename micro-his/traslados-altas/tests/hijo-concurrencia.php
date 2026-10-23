<?php

declare(strict_types=1);

// Proceso hijo de tests/concurrencia.php: una conexión PostgreSQL propia ejecuta un traslado real.
// Espera en una barrera (advisory lock compartido) para arrancar al mismo tiempo que el otro hijo.
// Uso: php hijo-concurrencia.php <clave-barrera> <actor> <estancia> <cama> ; DSN y clave en TRASLADOS_PG_DSN y TRASLADOS_PG_PASSWORD.

use MicroHis\TrasladosAltas\Application\ExitoConfirmado;
use MicroHis\TrasladosAltas\Application\RechazoConfirmado;
use MicroHis\TrasladosAltas\Application\TrasladoInterno;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;
use MicroHis\TrasladosAltas\Persistence\AutorizadorReferenciaActiva;
use MicroHis\TrasladosAltas\Persistence\PdoRegistroInternacion;

require __DIR__ . '/../bootstrap.php';

[, $clave, $actor, $estancia, $cama] = $argv;
$conectar = static fn (): PDO => new PDO((string) getenv('TRASLADOS_PG_DSN'), getenv('TRASLADOS_PG_USER') ?: 'postgres',
    (string) getenv('TRASLADOS_PG_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$barrera = $conectar();
$barrera->prepare('SELECT pg_advisory_lock_shared(CAST(:k AS bigint))')->execute(['k' => $clave]);

$resultado = (new TrasladoInterno(new SolicitudTraslado($estancia, $cama, 'Prueba de concurrencia'), $actor,
    new PdoRegistroInternacion($conectar()), new AutorizadorReferenciaActiva($conectar())))->ejecutar();
echo json_encode(['resultado' => (new ReflectionClass($resultado))->getShortName(),
    'motivo' => $resultado instanceof RechazoConfirmado ? $resultado->motivo->value : null,
    'estancia_destino' => $resultado instanceof ExitoConfirmado ? $resultado->liberacion->estanciaDestinoId : null]), PHP_EOL;
