<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Ci;

/**
 * Escribe un reporte JUnit mínimo para etapas que no lo generan solas
 * (php -l, Pint, runners de micro-his, guardia de rutas). Así el gate lee
 * un único formato para todas las etapas.
 */
final class JUnitWriter
{
    /** @var list<array{name: string, status: 'passed'|'failed'|'skipped', seconds: float, message: string}> */
    private array $cases = [];

    public function __construct(private readonly string $suite) {}

    /** @param 'passed'|'failed'|'skipped' $status */
    public function add(string $name, string $status, float $seconds = 0.0, string $message = ''): void
    {
        $this->cases[] = ['name' => $name, 'status' => $status, 'seconds' => $seconds, 'message' => $message];
    }

    public function failures(): int
    {
        return count(array_filter($this->cases, static fn (array $c): bool => $c['status'] === 'failed'));
    }

    public function write(string $file): void
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $doc->appendChild($doc->createElement('testsuites'));
        $suite = $root->appendChild($doc->createElement('testsuite'));
        $suite->setAttribute('name', $this->suite);
        $suite->setAttribute('tests', (string) count($this->cases));
        $suite->setAttribute('failures', (string) $this->failures());
        foreach ($this->cases as $c) {
            $case = $suite->appendChild($doc->createElement('testcase'));
            $case->setAttribute('name', $c['name']);
            $case->setAttribute('classname', $this->suite);
            $case->setAttribute('time', sprintf('%.3f', $c['seconds']));
            if ($c['status'] !== 'passed') {
                $child = $case->appendChild($doc->createElement($c['status'] === 'failed' ? 'failure' : 'skipped'));
                // Se recorta el mensaje: el log completo se conserva aparte como evidencia.
                $child->setAttribute('message', mb_substr($c['message'], 0, 500));
            }
        }
        is_dir(dirname($file)) || mkdir(dirname($file), 0775, true);
        $doc->save($file);
    }
}
