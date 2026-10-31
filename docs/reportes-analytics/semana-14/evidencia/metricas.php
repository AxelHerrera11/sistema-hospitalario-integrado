<?php
// Mide lo que se puede medir sin servidor. Uso: php docs/reportes-analytics/semana-14/evidencia/metricas.php
$raiz = dirname(__DIR__, 4);
chdir($raiz);
$correr = static function (string $comando): array { exec($comando . ' 2>&1', $salida, $codigo); return [$codigo, implode("\n", $salida)]; };
[$c1, $s1] = $correr('php micro-his/reportes-analytics/tests/run.php');
$ok = substr_count($s1, 'OK   ');
echo 'M1 escenarios del micro-HIS: ', $c1 === 0 ? "cumple ({$ok})" : 'NO CUMPLE', "\n";
[$c2] = $correr('php micro-his/reportes-analytics/tests/example-lsp.php');
echo 'M2 contrato LSP: ', $c2 === 0 ? 'cumple' : 'NO CUMPLE', "\n";
$archivos = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('micro-his/reportes-analytics', FilesystemIterator::SKIP_DOTS)), '/\.php$/');
$errores = 0; $total = 0;
foreach ($archivos as $archivo) { $total++; [$c] = $correr('php -l ' . escapeshellarg((string) $archivo)); $errores += $c === 0 ? 0 : 1; }
echo "M3 errores de sintaxis: {$errores} de {$total}\n";
[$c4] = $correr('node resources/js/modules/reportes/reportes.test.mjs');
[$c5] = $correr('node resources/js/modules/reportes/reportes.a11y.test.mjs');
echo 'M4 reglas y accesibilidad de la pantalla: ', $c4 === 0 && $c5 === 0 ? 'cumple' : 'NO CUMPLE', "\n";
$texto = '';
foreach (new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('micro-his/reportes-analytics/src', FilesystemIterator::SKIP_DOTS)), '/\.php$/') as $archivo) { $texto .= file_get_contents((string) $archivo); }
$danado = preg_match('/Ã|Â|�/u', $texto);
echo 'Control adicional, texto dañado en el código: ', $danado ? 'NO CUMPLE' : 'cumple', "\n";
