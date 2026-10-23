<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Ci;

/**
 * Lee reportes JUnit XML de PHPUnit (--log-junit) y de node --test
 * (--test-reporter=junit). En vez de confiar en los atributos agregados de
 * <testsuite>, que cada herramienta escribe distinto, cuenta cada <testcase>.
 */
final class JUnitReportReader
{
    /**
     * @return array{total: int, failed: int, skipped: int, duration_ms: int}
     *
     * @throws \RuntimeException si el archivo no existe o no es JUnit válido
     */
    public function read(string $file): array
    {
        $xml = is_file($file) ? file_get_contents($file) : false;
        if ($xml === false || trim($xml) === '') {
            throw new \RuntimeException("Reporte JUnit inexistente o vacío: {$file}");
        }
        $previous = libxml_use_internal_errors(true);
        // LIBXML_NONET: sin red. No se usa LIBXML_NOENT, así que las entidades externas no se expanden.
        $root = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($root === false || ! in_array($root->getName(), ['testsuites', 'testsuite'], true)) {
            throw new \RuntimeException("El archivo no es un reporte JUnit: {$file}");
        }

        $counts = ['total' => 0, 'failed' => 0, 'skipped' => 0, 'duration_ms' => 0];
        $seconds = 0.0;
        foreach ($root->xpath('//testcase') ?: [] as $case) {
            $counts['total']++;
            $seconds += (float) ($case['time'] ?? 0);
            if (isset($case->failure) || isset($case->error)) {
                $counts['failed']++;
            } elseif (isset($case->skipped)) {
                $counts['skipped']++;
            }
        }
        $counts['duration_ms'] = (int) round($seconds * 1000);

        return $counts;
    }
}
