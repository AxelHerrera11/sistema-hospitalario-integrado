<?php

declare(strict_types=1);

// Mide las métricas SQA que hoy son medibles en el micro-HIS ASII-14.
// Uso: php docs/catalogo-medicamentos/semana-14/evidencia/metricas.php

use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Persistence\InMemoryMedicationRepository;
use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;

$root = dirname(__DIR__, 4) . '/micro-his/catalogo-medicamentos';
require $root . '/bootstrap.php';

// M1: verificaciones aprobadas por suite (se ejecuta cada suite como proceso aparte).
$aprobadas = 0;
$suitesOk = 0;
$suites = ['run', 'pdo', 'repository', 'http'];
foreach ($suites as $suite) {
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("{$root}/tests/{$suite}.php") . ' 2>&1', $salida, $codigo);
    preg_match('/OK: (\d+) verificaciones/', implode("\n", $salida), $m);
    $aprobadas += (int) ($m[1] ?? 0);
    $suitesOk += $codigo === 0 ? 1 : 0;
    $salida = [];
}
printf("M1 suites en verde: %d/%d; verificaciones aprobadas: %d\n", $suitesOk, count($suites), $aprobadas);

// M2: errores de sintaxis en los PHP del micro-HIS.
$errores = 0;
$archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$total = 0;
foreach ($archivos as $archivo) {
    if ($archivo->getExtension() === 'php') {
        $total++;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($archivo->getPathname()) . ' 2>&1', $s, $c);
        $errores += $c === 0 ? 0 : 1;
    }
}
printf("M2 errores de lint: %d de %d archivos PHP\n", $errores, $total);

// M3: divergencias observables entre adaptadores con datos no ASCII.
$casos = [['ÁCIDO ACETILSALICÍLICO', 'ácido'], ['ÁCIDO ACETILSALICÍLICO', 'ACIDO'], ['Ñandú forte', 'ñandú'], ['Ñandú forte', 'nandu'], ['Amoxicilina', 'AMOXI']];
$divergencias = 0;
foreach ($casos as $i => [$nombre, $termino]) {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec((string) file_get_contents("{$root}/database/schema.sql"));
    $resultados = [];
    foreach ([new InMemoryMedicationRepository(), new PdoMedicationCatalog($pdo)] as $repo) {
        $repo->insert(Medication::register(['code' => "C-{$i}", 'name' => $nombre, 'active_ingredient' => $nombre, 'pharmacological_group' => 'Grupo']));
        $resultados[] = count($repo->search($termino, 50));
    }
    $divergencias += $resultados[0] === $resultados[1] ? 0 : 1;
    printf("   \"%s\" en \"%s\": InMemory %d, PDO %d\n", $termino, $nombre, ...$resultados);
}
printf("M3 divergencias entre adaptadores: %d de %d casos\n", $divergencias, count($casos));
exit($suitesOk === count($suites) && $errores === 0 ? 0 : 1);
