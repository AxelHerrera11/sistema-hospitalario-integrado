<?php

declare(strict_types=1);
/*
 * Runner minimo de pruebas del Micro-HIS Centro de Documentacion (sin PHPUnit).
 * Ejecutar: php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Documentacion\Application\ConsultarDocumentos;
use MicroHIS\Documentacion\Application\PublicarDocumento;
use MicroHIS\Documentacion\Domain\Documento;
use MicroHIS\Documentacion\Domain\DocumentoSinRolesException;
use MicroHIS\Documentacion\Domain\ErrorPersistenciaException;
use MicroHIS\Documentacion\Persistence\DocumentoRepositoryEnMemoria;
use MicroHIS\Documentacion\Persistence\DocumentoRepositoryQueFalla;

$pruebas = [];
$pruebas['dominio: rechaza documento sin roles'] = static function (): void {
    try {
        Documento::publicar('doc-1', 'Sin roles', []);
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (DocumentoSinRolesException $e) {
        assert($e->getMessage() === '422 documento_sin_roles');
    }
};
$pruebas['dominio: normaliza y detecta visibilidad por rol'] = static function (): void {
    $documento = Documento::publicar('doc-2', 'Manual', ['enfermeria', 'enfermeria', '']);
    assert($documento->roles === ['enfermeria']);
    assert($documento->visiblePara('enfermeria') === true);
    assert($documento->visiblePara('recepcion') === false);
};
$pruebas['aplicacion: camino feliz publica y lista por rol'] = static function (): void {
    $repositorio = new DocumentoRepositoryEnMemoria();
    (new PublicarDocumento($repositorio))->ejecutar('Manual de enfermeria', ['enfermeria']);
    $visibles = (new ConsultarDocumentos($repositorio))->listar('enfermeria');
    assert(count($visibles) === 1);
    assert($visibles[0]['titulo'] === 'Manual de enfermeria');
};
$pruebas['aplicacion: no lista documentos de otro rol'] = static function (): void {
    $repositorio = new DocumentoRepositoryEnMemoria();
    (new PublicarDocumento($repositorio))->ejecutar('Protocolo de admision', ['recepcion']);
    assert((new ConsultarDocumentos($repositorio))->listar('enfermeria') === []);
};
$pruebas['aplicacion: error de persistencia se propaga'] = static function (): void {
    try {
        (new PublicarDocumento(new DocumentoRepositoryQueFalla()))->ejecutar('Manual', ['admin']);
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (ErrorPersistenciaException $e) {
        assert($e->getMessage() === 'fallo simulado de persistencia');
    }
};

$total = 0;
$fallidas = 0;
foreach ($pruebas as $nombre => $prueba) {
    $total++;
    try {
        $prueba();
        echo "  [OK] {$nombre}\n";
    } catch (\Throwable $e) {
        $fallidas++;
        echo "  [FALLO] {$nombre}: {$e->getMessage()}\n";
    }
}
echo "\nResumen: {$total} pruebas, {$fallidas} fallidas.\n";
exit($fallidas === 0 ? 0 : 1);
