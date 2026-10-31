<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MicroHis\CentroDocumentacion\Application\ConsultarCatalogo;
use MicroHis\CentroDocumentacion\Domain\Documento;

$failures = 0;
$total = 0;

function check(string $label, bool $condition): void
{
    global $failures, $total;
    $total++;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $condition ? 0 : 1;
}

function rechaza(array $datos): bool
{
    try {
        Documento::desde($datos);
    } catch (InvalidArgumentException) {
        return true;
    }

    return false;
}

$base = ['id' => 'manual', 'titulo' => 'Manual', 'tipo' => 'manual', 'version' => '1.0', 'ruta' => 'docs/manual.md', 'roles' => ['medico']];
check('rechaza documento sin roles', rechaza(['roles' => ['', '']] + $base));
check('rechaza documento sin titulo', rechaza(['titulo' => '  '] + $base));
check('rechaza ruta fuera de docs/', rechaza(['ruta' => 'docs/../.env.md'] + $base) && rechaza(['ruta' => 'config/app.php'] + $base));

$catalogo = new ConsultarCatalogo([
    $base,
    ['id' => 'openapi', 'titulo' => 'OpenAPI v1', 'tipo' => 'contrato', 'version' => '0.1.0', 'ruta' => 'docs/openapi.yaml', 'roles' => ['admin_hospital', 'medico']],
]);
check('el medico ve los dos documentos', count($catalogo->visibles(['medico'])) === 2);
check('enfermeria no ve documentos de otros roles', $catalogo->visibles(['enfermeria']) === []);
check('un documento no visible se trata como inexistente', $catalogo->documento('manual', ['admin_hospital']) === null);
check('la union de roles de la sesion amplia la visibilidad', $catalogo->documento('manual', ['enfermeria', 'medico'])?->ruta === 'docs/manual.md');

echo "\n" . ($failures === 0 ? "OK: {$total} verificaciones aprobadas" : "ERROR: {$failures} de {$total} verificaciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
