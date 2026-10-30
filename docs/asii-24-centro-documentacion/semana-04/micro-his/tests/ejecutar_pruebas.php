<?php

declare(strict_types=1);
/*
 * Runner minimo de pruebas del Micro-HIS Centro de Documentacion (Semana 4).
 * Ejecutar: php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../autoload.php';

use MicroHIS\Documentacion\Application\ConsultarDocumentos;
use MicroHIS\Documentacion\Application\PublicarDocumento;
use MicroHIS\Documentacion\Infrastructure\DocumentoRepositoryInMemory;
use MicroHIS\Documentacion\Infrastructure\DocumentoRepositoryPDO;
use MicroHIS\Documentacion\Presentation\DocumentoController;

$pruebas = [];
$pruebas['repository pdo e in memory cumplen el mismo contrato'] = static function (): void {
    foreach ([new DocumentoRepositoryInMemory(), new DocumentoRepositoryPDO(new \PDO('sqlite::memory:'))] as $repo) {
        (new PublicarDocumento($repo))->ejecutar('Manual de enfermeria', ['enfermeria']);
        assert(count($repo->todos()) === 1);
    }
};
$pruebas['controlador: publica y lista por rol'] = static function (): void {
    $repo = new DocumentoRepositoryInMemory();
    $c = new DocumentoController(new PublicarDocumento($repo), new ConsultarDocumentos($repo));
    assert($c->publicar(['titulo' => 'Manual', 'roles' => ['enfermeria']])['status'] === 201);
    $lista = $c->listar(['rol' => 'enfermeria']);
    assert($lista['status'] === 200 && count($lista['body']['documentos']) === 1);
};
$pruebas['controlador: sin roles responde 422'] = static function (): void {
    $repo = new DocumentoRepositoryInMemory();
    $c = new DocumentoController(new PublicarDocumento($repo), new ConsultarDocumentos($repo));
    $r = $c->publicar(['titulo' => 'Sin roles', 'roles' => []]);
    assert($r['status'] === 422 && $r['body']['error'] === 'documento_sin_roles');
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
