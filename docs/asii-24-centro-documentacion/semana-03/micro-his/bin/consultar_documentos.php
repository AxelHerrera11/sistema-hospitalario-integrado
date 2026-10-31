<?php

declare(strict_types=1);
/*
 * Front controller CLI del Micro-HIS Centro de Documentacion (Presentation).
 * Uso:
 *   php bin/consultar_documentos.php --rol=enfermeria
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Documentacion\Application\ConsultarDocumentos;
use MicroHIS\Documentacion\Application\PublicarDocumento;
use MicroHIS\Documentacion\Persistence\DocumentoRepositoryEnMemoria;
use MicroHIS\Documentacion\Persistence\DocumentoRepositoryPDO;

$opciones = getopt('', ['rol:', 'sembrar', 'dsn:']);
$dsn = (string) ($opciones['dsn'] ?? getenv('MICRO_HIS_DSN') ?: '');
$repositorio = $dsn === ''
    ? new DocumentoRepositoryEnMemoria()
    : new DocumentoRepositoryPDO(new \PDO($dsn));

if (isset($opciones['sembrar'])) {
    $publicar = new PublicarDocumento($repositorio);
    $publicar->ejecutar('Manual de enfermeria', ['enfermeria', 'admin']);
    $publicar->ejecutar('Protocolo de admision', ['recepcion', 'admin']);
    $roles = (string) ($opciones['rol'] ?? '');
    if ($roles === '') {
        fwrite(STDOUT, "Sembrados 2 documentos\n");
        exit(0);
    }
}

$rol = (string) ($opciones['rol'] ?? '');
if ($rol === '') {
    fwrite(STDERR, "Error: falta --rol\n");
    exit(2);
}

$documentos = (new ConsultarDocumentos($repositorio))->listar($rol);
if ($documentos === []) {
    fwrite(STDOUT, "Sin documentos visibles para el rol {$rol}\n");
    exit(0);
}

foreach ($documentos as $documento) {
    fwrite(STDOUT, "OK {$documento['id']} {$documento['titulo']}\n");
}
exit(0);
